<?php

namespace App\Services;

use App\WorkUnit;
use Illuminate\Support\Facades\DB;

class PublicStatisticsService
{
    public function defaultMode()
    {
        return DB::table('statistics_demo_entries')->exists() ? 'demo' : 'live';
    }

    public function snapshot($unitId = null, $mode = 'live')
    {
        $units = WorkUnit::orderBy('id')->get(['id', 'name']);
        $selected = $unitId ? $units->firstWhere('id', (int) $unitId) : null;
        $channels = ['meet'=>0, 'chat'=>0, 'mace'=>0];
        $statuses = ['completed'=>0, 'active'=>0, 'cancelled'=>0];
        $byUnit = [];
        foreach ($units as $unit) {
            if (!$selected || $unit->id === $selected->id) $byUnit[$unit->name] = $this->unitRow($unit->name);
        }
        $purposes = [];
        $estimate = null;
        if ($mode === 'demo') {
            $samples = DB::table('statistics_demo_entries')->join('work_units','work_units.id','=','statistics_demo_entries.work_unit_id')
                ->select('work_units.name','channel','status','purpose')->selectRaw('COUNT(*) AS total, SUM(distance_meters) AS distance, SUM(duration_seconds) AS duration, SUM(fuel_cost) AS fuel');
            if ($selected) $samples->where('work_unit_id',$selected->id);
            $distance=0; $seconds=0; $fuel=0; $routeCount=0;
            foreach ($samples->groupBy('work_units.name','channel','status','purpose')->get() as $sample) {
                $this->add($byUnit,$channels,$statuses,$sample->name,$sample->channel,$sample->status,(int)$sample->total);
                $purposes[$sample->purpose]=($purposes[$sample->purpose] ?? 0)+(int)$sample->total;
                $distance+=(int)$sample->distance; $seconds+=(int)$sample->duration; $fuel+=(int)$sample->fuel; $routeCount+=(int)$sample->total;
            }
            $estimate=['distance_km'=>$routeCount ? $distance/1000 : null,'minutes'=>$routeCount ? (int)round($seconds/60) : null,
                'fuel_cost'=>$routeCount ? $fuel : null,'route_count'=>$routeCount,'fuel_count'=>$routeCount];
        } else {
        $meetings = DB::table('meetings')->select('work_unit', 'channel', 'status')->selectRaw('COUNT(*) AS total');
        $chats = DB::table('chat_rooms')->leftJoin('services', 'services.id', '=', 'chat_rooms.service_id')
            ->select('chat_rooms.work_unit', 'chat_rooms.status', 'services.category')->selectRaw('COUNT(*) AS total');
        if ($selected) {
            $meetings->where('work_unit', $selected->name);
            $chats->where('chat_rooms.work_unit', $selected->name);
        }
        foreach ($meetings->groupBy('work_unit', 'channel', 'status')->get() as $row) {
            $channel = $row->channel === 'mace' ? 'mace' : 'meet';
            $status = ['ended'=>'completed', 'cancelled'=>'cancelled'][$row->status] ?? 'active';
            $this->add($byUnit, $channels, $statuses, $row->work_unit, $channel, $status, (int) $row->total);
            $purposes['Konsultasi PTSP'] = ($purposes['Konsultasi PTSP'] ?? 0) + (int) $row->total;
        }
        $categoryLabels = ['perkara'=>'Perkara', 'persidangan'=>'Persidangan', 'kepaniteraan'=>'Kepaniteraan',
            'informasi'=>'Informasi layanan', 'pengaduan'=>'Informasi pengaduan'];
        foreach ($chats->groupBy('chat_rooms.work_unit', 'chat_rooms.status', 'services.category')->get() as $row) {
            $this->add($byUnit, $channels, $statuses, $row->work_unit, 'chat', $row->status === 'closed' ? 'completed' : 'active', (int) $row->total);
            $purpose = $categoryLabels[$row->category] ?? 'Konsultasi umum';
            $purposes[$purpose] = ($purposes[$purpose] ?? 0) + (int) $row->total;
        }
        }
        $rows = array_values($byUnit);
        usort($rows, function ($a, $b) { return ($b['total'] <=> $a['total']) ?: strcmp($a['name'], $b['name']); });
        arsort($purposes);
        $total = array_sum($channels);
        return [
            'units'=>$units, 'selected_unit'=>$selected, 'total'=>$total, 'channels'=>$channels, 'statuses'=>$statuses,
            'by_unit'=>$rows, 'purposes'=>$purposes, 'online'=>$channels['meet'] + $channels['chat'],
            'served_units'=>count(array_filter($rows, function ($row) { return $row['total'] > 0; })),
            'max_unit_total'=>max(array_merge([1], array_column($rows, 'total'))),
            'max_purpose_total'=>max(array_merge([1], array_values($purposes))),
            'estimate'=>$estimate ?? app(TravelEstimateService::class)->aggregate(optional($selected)->id),
            'data_mode'=>$mode === 'demo' ? 'demo' : 'live',
        ];
    }

    private function unitRow($name)
    {
        $short = str_replace(['Pengadilan Tinggi Agama ', 'Pengadilan Agama '], ['PTA ', 'PA '], $name);
        return ['name'=>$name, 'short'=>$short, 'total'=>0, 'channels'=>['meet'=>0,'chat'=>0,'mace'=>0],
            'statuses'=>['completed'=>0,'active'=>0,'cancelled'=>0]];
    }

    private function add(&$units, &$channels, &$statuses, $name, $channel, $status, $count)
    {
        $name = $name ?: 'Satker belum tercatat';
        if (!isset($units[$name])) $units[$name] = $this->unitRow($name);
        $channels[$channel] += $count;
        $statuses[$status] += $count;
        $units[$name]['total'] += $count;
        $units[$name]['channels'][$channel] += $count;
        $units[$name]['statuses'][$status] += $count;
    }
}
