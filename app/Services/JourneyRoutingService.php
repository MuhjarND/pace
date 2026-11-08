<?php

namespace App\Services;

use App\ConsultationJourney;
use App\GuestTicket;
use App\Region;
use App\WorkUnit;
use Illuminate\Support\Facades\Http;

class JourneyRoutingService
{
    public function record(GuestTicket $ticket, WorkUnit $unit, ?Region $region)
    {
        $origin = $region && $region->latitude !== null && $region->longitude !== null
            ? ['latitude'=>(float)$region->latitude, 'longitude'=>(float)$region->longitude] : null;
        return ConsultationJourney::create(['guest_ticket_id'=>$ticket->id, 'work_unit_id'=>$unit->id,
            'origin_region_code'=>optional($region)->code, 'origin'=>$origin, 'destination_latitude'=>$unit->latitude, 'destination_longitude'=>$unit->longitude,
            'km_per_liter'=>$unit->travel_km_per_liter, 'fuel_price'=>$unit->travel_fuel_price,
            'status'=>!$region ? 'missing_location' : (!$origin ? 'missing_origin' : ($unit->latitude === null || $unit->longitude === null ? 'missing_destination' : 'pending'))]);
    }

    public function resolve(ConsultationJourney $journey)
    {
        // A durable claim prevents duplicate submissions from querying the route twice.
        if (!ConsultationJourney::whereKey($journey->id)->where('status','pending')->update(['status'=>'resolving','updated_at'=>now()])) return;
        $origin = $journey->origin;
        $base = rtrim((string)config('services.travel.routing_url'), '/');
        $profile = (string)config('services.travel.routing_profile','driving');
        $point = function ($lng, $lat) { return number_format((float)$lng,7,'.','').','.number_format((float)$lat,7,'.',''); };
        if (!$origin || !isset($origin['latitude'],$origin['longitude']) || !preg_match('~\Ahttps?://[^\s?#]+\z~', $base) || !preg_match('/\A[a-z_]+\z/', $profile)) {
            $journey->update(['status'=>'route_unavailable']); return;
        }
        // Route the outward AND return journeys; one-way roads can make the legs different.
        $from = $point($origin['longitude'], $origin['latitude']);
        $to = $point($journey->destination_longitude, $journey->destination_latitude);
        try {
            $response = Http::withHeaders(['User-Agent'=>'PACE-PTSP/1.0','Accept'=>'application/json'])
                ->withOptions(['connect_timeout'=>2,'allow_redirects'=>false])->timeout(6)
                ->get($base.'/route/v1/'.$profile.'/'.$from.';'.$to.';'.$from, [
                    'overview'=>'false','steps'=>'false','alternatives'=>'false','radiuses'=>'2000;2000;2000',
                ]);
            $body = $response->json();
            $route = $body['routes'][0] ?? null;
            if (!$response->successful() || ($body['code'] ?? '') !== 'Ok' || !is_array($route)
                || !is_numeric($route['distance'] ?? null) || !is_numeric($route['duration'] ?? null)) {
                $journey->update(['status'=>'route_unavailable']); return;
            }
            $distance = (float)$route['distance']; $duration = (float)$route['duration'];
            if (!is_finite($distance) || !is_finite($duration) || $distance < 0 || $duration < 0 || $distance > 100000000 || $duration > 31536000) {
                $journey->update(['status'=>'route_unavailable']); return;
            }
            $distance = (int)round($distance);
            $cost = $journey->km_per_liter > 0 && $journey->fuel_price > 0
                ? (int)round($distance / 1000 / $journey->km_per_liter * $journey->fuel_price) : null;
            $journey->update(['status'=>'routed','distance_meters'=>(int)round($distance),
                'duration_seconds'=>(int)round($duration),'fuel_cost'=>$cost,'source'=>'OSRM','calculated_at'=>now()]);
        } catch (\Throwable $e) {
            // Never log provider URLs containing the applicant's coordinates or block their consultation.
            $journey->update(['status'=>'route_unavailable']);
        }
    }
}
