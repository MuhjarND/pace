<?php

namespace App\Services;

use App\WorkUnit;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class CaseStatusLookup
{
    public static function normalize($number)
    {
        return is_string($number) ? strtoupper(preg_replace('/\s+/u', '', trim($number))) : '';
    }

    public static function validNumber($number)
    {
        return is_string($number) && preg_match('/\A[0-9]{1,7}\/[A-Z][A-Z0-9.()-]{0,24}\/(?:19|20)[0-9]{2}\/PA\.[A-Z0-9]{2,12}\z/', $number) === 1;
    }

    public function lookup(WorkUnit $unit, $number)
    {
        $unavailable = ['state'=>'unavailable', 'case'=>null, 'synced_at'=>null];
        $url = config('services.kasuari.case_status_url');
        $token = config('services.kasuari.token');
        $parts = is_string($url) ? parse_url($url) : false;
        if (!$parts || empty($parts['host']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['query'])
            || isset($parts['fragment']) || !is_string($token) || strlen($token) < 48
            || (($parts['scheme'] ?? '') !== 'https' && !(($parts['scheme'] ?? '') === 'http'
                && in_array($parts['host'], ['localhost', '127.0.0.1', '::1'], true)))) {
            return $unavailable;
        }
        try {
            $response = Http::withHeaders(['X-PTSP-Key'=>$token])->acceptJson()->asForm()
                ->timeout(8)->withOptions(['connect_timeout'=>3, 'allow_redirects'=>false])
                ->post($url, ['court'=>$unit->name, 'case_number'=>$number]);
            if (!$response->successful() || strlen($response->body()) > 16384) return $unavailable;
            $data = $response->json();
            if (!is_array($data) || ($data['version'] ?? null) !== 1
                || !in_array($data['state'] ?? null, ['found', 'not_found', 'unavailable'], true)) return $unavailable;
            $synced = $this->timestamp($data['synced_at'] ?? null);
            if ($data['state'] === 'not_found' && ($data['court_key'] ?? null) !== $this->courtKey($unit->name)) return $unavailable;
            if ($data['state'] !== 'found') return ['state'=>$data['state'], 'case'=>null, 'synced_at'=>$synced];
            $case = $data['case'] ?? null;
            // Reject mismatched responses and project an explicit public allowlist a second time.
            if (!is_array($case) || !is_string($case['number'] ?? null) || static::normalize($case['number']) !== $number
                || ($data['court_key'] ?? null) !== $this->courtKey($unit->name)) return $unavailable;
            return ['state'=>'found', 'synced_at'=>$synced,
                'certificate'=>$this->certificate($data['divorce_certificate'] ?? null, $case['type'] ?? null), 'case'=>[
                'number'=>$this->text($case['number'], 100), 'type'=>$this->text($case['type'] ?? null, 160),
                'stage'=>$this->text($case['stage'] ?? null, 160), 'process'=>$this->text($case['process'] ?? null, 200),
                'registered_on'=>$this->date($case['registered_on'] ?? null), 'decided_on'=>$this->date($case['decided_on'] ?? null),
            ]];
        } catch (\Throwable $e) {
            // Do not log request credentials, case numbers, or upstream bodies.
            return $unavailable;
        }
    }

    private function certificate($value, $caseType)
    {
        $empty = ['state'=>'unavailable', 'issued_on'=>null, 'synced_at'=>null, 'collections'=>[]];
        if (!is_array($value)) {
            if (is_string($caseType) && trim($caseType) !== '' && stripos($caseType, 'cerai') === false) $empty['state'] = 'not_applicable';
            return $empty;
        }
        $state = $value['state'] ?? null;
        if (!in_array($state, ['unavailable','not_applicable','not_recorded','incomplete','issued'], true)) return $empty;
        if (in_array($state, ['unavailable','not_applicable'], true)) return array_merge($empty, ['state'=>$state]);
        $synced = $this->timestamp($value['synced_at'] ?? null);
        if (!$synced) return $empty;
        $issued = $this->date($value['issued_on'] ?? null);
        if ($state === 'issued' && !$issued) $state = 'incomplete';
        $collections = [];
        $entries = is_array($value['collections'] ?? null) ? $value['collections'] : [];
        foreach (['party1','party2'] as $party) {
            $entry = $entries[$party] ?? null;
            $date = is_array($entry) ? $this->date($entry['collected_on'] ?? null) : null;
            $entryState = is_array($entry) ? ($entry['state'] ?? null) : null;
            if ($entryState === 'collected' && $date) $collections[$party] = ['state'=>'collected','collected_on'=>$date];
            elseif ($entryState === 'not_recorded' && !$date) $collections[$party] = ['state'=>'not_recorded','collected_on'=>null];
            else $collections[$party] = ['state'=>'unavailable','collected_on'=>null];
        }
        // Only status and dates cross into public UI; ignore certificate numbers, people and document URLs.
        return ['state'=>$state, 'issued_on'=>$issued, 'synced_at'=>$synced, 'collections'=>$collections];
    }

    private function courtKey($name)
    {
        $name = preg_replace('/\A(?:pengadilan\s+agama|pa[.\s]+)\s*/i', '', trim($name));
        return strtolower(preg_replace('/[^a-z0-9]/i', '', $name));
    }

    private function text($value, $max)
    {
        if (!is_string($value) || trim($value) === '') return null;
        return mb_substr(trim(preg_replace('/\s+/u', ' ', strip_tags($value))), 0, $max);
    }

    private function date($value)
    {
        if (!is_string($value) || !preg_match('/\A([0-9]{4})-([0-9]{2})-([0-9]{2})\z/', $value, $m)
            || !checkdate((int)$m[2], (int)$m[3], (int)$m[1])) return null;
        return $value;
    }

    private function timestamp($value)
    {
        if (!is_string($value) || !preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(?:Z|[+-][0-9]{2}:[0-9]{2})\z/', $value)) return null;
        if (!$this->date(substr($value, 0, 10))) return null;
        try { return Carbon::parse($value)->timezone(config('ptsp.timezone'))->format('d/m/Y H:i').' WIT'; }
        catch (\Throwable $e) { return null; }
    }
}
