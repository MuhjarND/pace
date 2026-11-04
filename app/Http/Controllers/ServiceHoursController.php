<?php

namespace App\Http\Controllers;

use App\AdminActivityLog;
use App\ServiceHoliday;
use App\ServiceHour;
use App\WorkUnit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ServiceHoursController extends Controller
{
    public function __construct() { $this->middleware('auth'); }

    private function unit()
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->isOperator(), 403);
        return WorkUnit::where('name', auth()->user()->work_unit)->firstOrFail();
    }

    public function index()
    {
        $unit = $this->unit()->load('hours');
        $hours = $unit->hours->keyBy('weekday');
        $holidays = $unit->holidays()->where('date', '>=', Carbon::now(config('ptsp.timezone'))->toDateString())->orderBy('date')->paginate(10);
        $days = [0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];
        return view('operations.hours', compact('unit', 'hours', 'holidays', 'days'));
    }

    public function update(Request $request)
    {
        $unit = $this->unit();
        $validator = Validator::make($request->all(), [
            'schedule_enabled' => 'required|boolean', 'daily_quota' => 'nullable|integer|min:1|max:500',
            'hours' => 'required|array|size:7', 'hours.*' => 'required|array', 'hours.*.is_open' => 'required|boolean',
            'hours.*.opens_at' => 'nullable|date_format:H:i', 'hours.*.closes_at' => 'nullable|date_format:H:i',
            'hours.*.break_start' => 'nullable|date_format:H:i', 'hours.*.break_end' => 'nullable|date_format:H:i',
        ]);
        $validator->after(function ($validator) use ($request) {
            $rows = $request->input('hours', []);
            if (!is_array($rows)) { return; }
            $keys = array_keys($rows); sort($keys);
            if ($keys !== range(0, 6)) { $validator->errors()->add('hours', 'Lengkapi pengaturan untuk tujuh hari.'); return; }
            $openDays = 0;
            foreach ($rows as $day => $row) {
                if (!is_array($row) || empty($row['is_open'])) { continue; }
                $openDays++;
                $open = $row['opens_at'] ?? ''; $close = $row['closes_at'] ?? '';
                $break = $row['break_start'] ?? ''; $end = $row['break_end'] ?? '';
                if (!$open || !$close || $close <= $open) { $validator->errors()->add("hours.$day.opens_at", 'Jam tutup harus sesudah jam buka pada hari yang sama.'); }
                if (($break || $end) && (!$break || !$end || $break <= $open || $end <= $break || $end >= $close)) {
                    $validator->errors()->add("hours.$day.break_start", 'Isi awal dan akhir istirahat di antara jam buka dan tutup.');
                }
            }
            if ($request->boolean('schedule_enabled') && !$openDays) { $validator->errors()->add('hours', 'Aktifkan sedikitnya satu hari layanan sebelum menerapkan jadwal.'); }
        });
        $data = $validator->validate();
        DB::transaction(function () use ($unit, $data) {
            $locked = WorkUnit::whereKey($unit->id)->lockForUpdate()->firstOrFail();
            $locked->update(['schedule_enabled' => (bool) $data['schedule_enabled'], 'daily_quota' => $data['daily_quota'] ?? null]);
            foreach ($data['hours'] as $day => $row) {
                $open = !empty($row['is_open']);
                ServiceHour::updateOrCreate(['work_unit_id' => $unit->id, 'weekday' => $day], [
                    'is_open' => $open, 'opens_at' => $open ? $row['opens_at'] : null, 'closes_at' => $open ? $row['closes_at'] : null,
                    'break_start' => $open ? ($row['break_start'] ?? null) : null, 'break_end' => $open ? ($row['break_end'] ?? null) : null,
                ]);
            }
            AdminActivityLog::create(['actor_id' => auth()->id(), 'action' => 'hours.updated', 'target_type' => 'work_unit', 'target_id' => $unit->id, 'changes' => ['schedule_enabled' => $locked->schedule_enabled, 'daily_quota' => $locked->daily_quota]]);
        });
        return redirect()->route('operations.hours')->with('success', 'Jam layanan disimpan. Penjadwalan baru mengikuti pengaturan ini; konsultasi yang sudah terdaftar tetap tersimpan.');
    }

    public function storeHoliday(Request $request)
    {
        $unit = $this->unit();
        $today = Carbon::now(config('ptsp.timezone'))->toDateString();
        $data = $request->validate(['date' => 'required|date_format:Y-m-d|after_or_equal:'.$today, 'name' => 'required|string|max:150']);
        DB::transaction(function () use ($unit, $data) {
            WorkUnit::whereKey($unit->id)->lockForUpdate()->firstOrFail();
            if (ServiceHoliday::where('work_unit_id', $unit->id)->where('date', $data['date'])->exists()) {
                throw ValidationException::withMessages(['date' => 'Tanggal ini sudah tercatat sebagai hari tutup.']);
            }
            $holiday = ServiceHoliday::create($data + ['work_unit_id' => $unit->id]);
            AdminActivityLog::create(['actor_id' => auth()->id(), 'action' => 'holiday.created', 'target_type' => 'work_unit', 'target_id' => $unit->id, 'changes' => ['date' => $holiday->date]]);
        });
        return redirect()->route('operations.hours')->with('success', 'Hari tutup ditambahkan. Penjadwalan yang sudah ada tetap dapat dilayani.');
    }

    public function destroyHoliday($id)
    {
        $unit = $this->unit();
        DB::transaction(function () use ($unit, $id) {
            WorkUnit::whereKey($unit->id)->lockForUpdate()->firstOrFail();
            $holiday = ServiceHoliday::where('work_unit_id', $unit->id)->findOrFail($id);
            AdminActivityLog::create(['actor_id' => auth()->id(), 'action' => 'holiday.removed', 'target_type' => 'work_unit', 'target_id' => $unit->id, 'changes' => ['date' => $holiday->date]]);
            $holiday->delete();
        });
        return redirect()->route('operations.hours')->with('success', 'Hari tutup dihapus. Jadwal mingguan berlaku kembali pada tanggal tersebut.');
    }
}
