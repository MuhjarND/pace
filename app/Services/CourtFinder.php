<?php
namespace App\Services;
use App\CourtTerritory;
use App\Region;

class CourtFinder
{
    public function find(Region $region)
    {
        $codes = $region->ancestors();
        $maps = CourtTerritory::with('unit')->whereIn('region_code', $codes)->get()->keyBy('region_code');
        foreach ($codes as $code) {
            if (isset($maps[$code])) {
                $map = $maps[$code];
                // A draft child explicitly blocks inheriting an older parent decision.
                return $map->is_verified && $map->effective_date->lte(now()->timezone(config('ptsp.timezone'))->startOfDay()) ? $map : null;
            }
        }
        return null;
    }
}
