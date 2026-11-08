<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class TravelEstimateService
{
    public function aggregate($unitId = null)
    {
        $query = DB::table('consultation_journeys')->where('status', 'routed')->whereNotNull('distance_meters')->whereNotNull('duration_seconds');
        if ($unitId) $query->where('work_unit_id', $unitId);
        $row = $query->selectRaw('COUNT(*) AS route_count, SUM(distance_meters) AS distance, SUM(duration_seconds) AS duration, COUNT(fuel_cost) AS fuel_count, SUM(fuel_cost) AS fuel')->first();
        $count = (int)$row->route_count;
        return ['distance_km'=>$count ? (float)$row->distance / 1000 : null,
            'minutes'=>$count ? (int)round((float)$row->duration / 60) : null,
            'fuel_cost'=>(int)$row->fuel_count ? (int)$row->fuel : null,
            'route_count'=>$count, 'fuel_count'=>(int)$row->fuel_count];
    }
}
