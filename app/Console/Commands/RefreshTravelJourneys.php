<?php

namespace App\Console\Commands;

use App\ConsultationJourney;
use App\Services\JourneyRoutingService;
use Illuminate\Console\Command;

class RefreshTravelJourneys extends Command
{
    protected $signature = 'ptsp:refresh-travel {--limit=100}';
    protected $description = 'Retry recorded applicant-to-court routes after court coordinates or fuel references are configured';

    public function handle(JourneyRoutingService $routing)
    {
        $limit = (int)$this->option('limit');
        if ($limit < 1 || $limit > 500) { $this->error('Limit harus antara 1 dan 500.'); return 1; }
        $rows = ConsultationJourney::whereNotNull('origin_region_code')->where(function ($query) {
            $query->whereIn('status',['pending','missing_origin','missing_destination','route_unavailable'])
                ->orWhere(function ($stale) { $stale->where('status','resolving')->where('updated_at','<',now()->subMinutes(5)); })
                ->orWhere(function ($fuel) { $fuel->where('status','routed')->whereNull('fuel_cost'); });
        })->where(function($ready){
            $ready->where(function($fuel){$fuel->where('status','routed')->whereHas('unit',function($unit){
                $unit->where('travel_km_per_liter','>',0)->where('travel_fuel_price','>',0);
            });})->orWhere(function($route){$route->where('status','!=','routed')
                ->whereHas('originRegion',function($region){$region->whereNotNull('latitude')->whereNotNull('longitude');})
                ->whereHas('unit',function($unit){$unit->whereNotNull('latitude')->whereNotNull('longitude');});});
        })->with('unit','originRegion')->orderBy('id')->limit($limit)->get();
        $processed = 0;
        foreach ($rows as $journey) {
            $unit = $journey->unit;
            if (!$unit) continue;
            if ($journey->status === 'routed') {
                if ($unit->travel_km_per_liter > 0 && $unit->travel_fuel_price > 0) {
                    $journey->update(['km_per_liter'=>$unit->travel_km_per_liter,'fuel_price'=>$unit->travel_fuel_price,
                        'fuel_cost'=>(int)round($journey->distance_meters / 1000 / $unit->travel_km_per_liter * $unit->travel_fuel_price)]);
                    $processed++;
                }
                continue;
            }
            if ($unit->latitude === null || $unit->longitude === null) continue;
            $region = $journey->originRegion;
            if (!$region || $region->latitude === null || $region->longitude === null) continue;
            $journey->update(['origin'=>['latitude'=>(float)$region->latitude,'longitude'=>(float)$region->longitude],
                'destination_latitude'=>$unit->latitude,'destination_longitude'=>$unit->longitude,
                'km_per_liter'=>$unit->travel_km_per_liter,'fuel_price'=>$unit->travel_fuel_price,'status'=>'pending']);
            $routing->resolve($journey);
            $processed++;
        }
        $this->info($processed.' perjalanan diproses. Data tanpa lokasi asal tidak diperkirakan.');
        return 0;
    }
}
