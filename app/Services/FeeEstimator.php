<?php
namespace App\Services;
use App\FeeSchedule;
use App\Region;
use App\WorkUnit;

class FeeEstimator
{
    public function estimate(WorkUnit $unit, $type, array $parties)
    {
        $today = now()->timezone(config('ptsp.timezone'))->toDateString();
        $schedule = FeeSchedule::where('work_unit_id', $unit->id)->where('case_type', $type)->where('is_published', true)
            ->where('effective_date', '<=', $today)->where(function ($q) use ($today) { $q->whereNull('expires_on')->orWhere('expires_on', '>=', $today); })
            ->orderByDesc('effective_date')->orderByDesc('id')->first();
        $missing = ['available'=>false, 'message'=>'Tarif terverifikasi belum tersedia. Perlu konfirmasi PTSP.', 'total'=>null, 'lines'=>[], 'schedule'=>$schedule];
        if (!$schedule) return $missing;
        $lines = [];
        $radii = $schedule->radii()->get()->keyBy('region_code');
        foreach ($schedule->components as $component) {
            $basis = $component['basis'];
            $quantity = (int) $component['quantity'];
            if ($basis === 'fixed') {
                $lines[] = ['label'=>$component['label'], 'amount'=>(int)$component['amount'] * $quantity];
                continue;
            }
            $role = strpos($basis, 'applicant') !== false ? 'applicant' : 'respondent';
            foreach ($parties as $i => $party) {
                if ($party['role'] !== $role) continue;
                $amount = (int)$component['amount'];
                $suffix = '';
                if (strpos($basis, 'radius_') === 0) {
                    $region = Region::findOrFail($party['region_code']);
                    $radius = null;
                    foreach ($region->ancestors() as $code) { if (isset($radii[$code])) { $radius = $radii[$code]; break; } }
                    if (!$radius) return array_merge($missing, ['message'=>'Radius biaya untuk salah satu pihak belum tersedia. Perlu konfirmasi PTSP.']);
                    $amount = (int)$radius->amount;
                    $suffix = ' · '.$radius->label;
                }
                $lines[] = ['label'=>$component['label'].' · Pihak '.($i + 1).$suffix.' × '.$quantity, 'amount'=>$amount * $quantity];
            }
        }
        return ['available'=>true, 'total'=>array_sum(array_column($lines, 'amount')), 'lines'=>$lines, 'schedule'=>$schedule, 'message'=>'Perkiraan panjar berdasarkan tarif yang dipublikasikan satker. Jumlah akhir dikonfirmasi pengadilan.'];
    }
}
