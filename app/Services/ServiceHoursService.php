<?php

namespace App\Services;

use App\Meeting;
use App\WorkUnit;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class ServiceHoursService
{
    public function availability(WorkUnit $unit, Carbon $date)
    {
        $zone = config('ptsp.timezone', 'Asia/Jayapura');
        $now = Carbon::now($zone);
        $date = $date->copy()->setTimezone($zone)->startOfDay();
        $result = ['date' => $date->toDateString(), 'slots' => [], 'can_now' => false, 'message' => '', 'quota_remaining' => null, 'timezone' => 'WIT'];
        if (!$unit->is_active) { return array_merge($result, ['message' => 'Satker sedang tidak menerima konsultasi baru.']); }
        if ($date->lt($now->copy()->startOfDay()) || $date->gt($now->copy()->startOfDay()->addDays(config('ptsp.consultation_booking_days', 365)))) {
            return array_merge($result, ['message' => 'Tanggal berada di luar rentang penjadwalan.']);
        }

        $hours = null;
        if ($unit->schedule_enabled) {
            $unit->loadMissing('hours', 'holidays');
            $holiday = $unit->holidays->firstWhere('date', $date->toDateString());
            if ($holiday) { return array_merge($result, ['message' => 'Layanan tutup: '.$holiday->name.'.']); }
            $hours = $unit->hours->firstWhere('weekday', $date->dayOfWeek);
            if (!$hours || !$hours->is_open) { return array_merge($result, ['message' => 'Tidak ada jam layanan pada hari ini.']); }
            if ($unit->daily_quota !== null) {
                $used = Meeting::where('work_unit', $unit->name)->where('queue_date', $date->toDateString())->where('status', '!=', 'cancelled')->count();
                $result['quota_remaining'] = max(0, $unit->daily_quota - $used);
                if ($result['quota_remaining'] === 0) { return array_merge($result, ['message' => 'Kuota konsultasi tanggal ini sudah penuh. Pilih tanggal lain.']); }
            }
        }
        $accepts = function ($time) use ($hours) {
            return !$hours || ($time >= $hours->opens_at && $time < $hours->closes_at
                && (!$hours->break_start || $time < $hours->break_start || $time >= $hours->break_end));
        };
        $result['can_now'] = $date->isSameDay($now) && $accepts($now->format('H:i'));
        for ($minute = 0; $minute < 1440; $minute += 15) {
            $slot = $date->copy()->addMinutes($minute);
            if ($slot->gte($now) && $accepts($slot->format('H:i'))) { $result['slots'][] = $slot->format('H:i'); }
        }
        $result['message'] = $result['can_now'] || count($result['slots'])
            ? ($unit->schedule_enabled ? 'Jadwal mengikuti jam layanan satker. Waktu ditampilkan dalam WIT.' : 'Jam layanan belum dibatasi oleh satker. Waktu ditampilkan dalam WIT.')
            : 'Tidak ada waktu tersedia pada tanggal ini. Pilih tanggal lain.';
        return $result;
    }

    // The caller holds the work_units row lock, including the quota check and insert.
    public function assertBookable(WorkUnit $unit, Carbon $start, $immediate)
    {
        $local = $start->copy()->setTimezone(config('ptsp.timezone', 'Asia/Jayapura'));
        $available = $this->availability($unit, $local);
        $valid = $immediate ? $available['can_now'] : in_array($local->format('H:i'), $available['slots'], true);
        if (!$valid) {
            $message = $available['message'];
            if (count($available['slots']) || $available['can_now']) { $message = 'Waktu tersebut tidak tersedia. Pilih jadwal layanan yang ditampilkan.'; }
            throw ValidationException::withMessages(['consultation_time' => $message]);
        }
    }
}
