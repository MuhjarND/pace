<?php

namespace App\Http\Controllers;

use App\Services\CaseStatusLookup;
use App\WorkUnit;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CaseStatusController extends Controller
{
    public function index(Request $request)
    {
        $units = $this->units();
        $unit = $units->firstWhere('id', (int)$request->query('unit'));
        return $this->page($units, $unit, '', null);
    }

    public function lookup(Request $request, CaseStatusLookup $lookup, RateLimiter $limiter)
    {
        $units = $this->units();
        $key = 'public-case-lookup:'.hash('sha256', (string)$request->ip());
        if ($limiter->tooManyAttempts($key, 10)) {
            $unit = $units->firstWhere('id', (int)$request->input('work_unit_id'));
            $number = is_string($request->input('case_number')) ? mb_substr($request->input('case_number'), 0, 100) : '';
            return $this->page($units, $unit, $number, ['state'=>'rate_limited', 'case'=>null, 'synced_at'=>null], 429)
                ->header('Retry-After', $limiter->availableIn($key));
        }
        $limiter->hit($key, 60);
        $data = $request->validate([
            'work_unit_id'=>['required', 'integer', Rule::in($units->pluck('id')->all())],
            'case_number'=>'required|string|max:100',
        ], ['work_unit_id.*'=>'Pilih pengadilan agama yang menangani perkara.', 'case_number.required'=>'Masukkan nomor perkara lengkap.'],
            ['case_number'=>'nomor perkara']);
        $number = CaseStatusLookup::normalize($data['case_number']);
        if (!CaseStatusLookup::validNumber($number)) {
            throw ValidationException::withMessages(['case_number'=>'Gunakan nomor perkara lengkap, misalnya 123/Pdt.G/2026/PA.Srg.']);
        }
        $unit = $units->firstWhere('id', (int)$data['work_unit_id']);
        $result = $lookup->lookup($unit, $number);
        $status = $result['state'] === 'unavailable' ? 503 : 200;
        return $this->page($units, $unit, $data['case_number'], $result, $status);
    }

    private function units()
    {
        return WorkUnit::where('is_active', true)->where('name', 'like', 'Pengadilan Agama %')->orderBy('name')->get();
    }

    private function page($units, $unit, $number, $result, $status = 200)
    {
        return response()->view('public.case-status', compact('units', 'unit', 'number', 'result'), $status)
            ->header('Cache-Control', 'no-store, private')->header('Referrer-Policy', 'no-referrer')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
