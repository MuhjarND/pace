<?php

namespace App\Http\Controllers;

use App\ConsultationSetting;
use App\Meeting;
use App\Rules\GoogleMeetUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConsultationAvailabilityController extends Controller
{
    public function __construct() { $this->middleware('auth'); }

    private function unit()
    {
        $user = auth()->user();
        abort_unless(($user->isAdmin() || $user->isOperator()) && in_array($user->work_unit, \App\WorkUnit::knownNames(), true), 403);
        return $user->work_unit;
    }

    public function index()
    {
        $unit = $this->unit();
        $setting = ConsultationSetting::where('work_unit', $unit)->first();
        return view('meetings.manage', compact('setting', 'unit'));
    }

    public function settings(Request $request)
    {
        $unit = $this->unit();
        $data = $request->validate(['join_url' => ['required', 'string', 'max:255', new GoogleMeetUrl]]);
        DB::transaction(function () use ($unit, $data) {
            \App\WorkUnit::where('name', $unit)->lockForUpdate()->firstOrFail();
            $url = GoogleMeetUrl::normalize($data['join_url']);
            $setting = ConsultationSetting::firstOrCreate(['work_unit' => $unit], ['join_url' => $url]);
            ConsultationSetting::whereKey($setting->id)->lockForUpdate()->firstOrFail()->update(['join_url' => $url]);
            Meeting::where('work_unit', $unit)->whereIn('status', ['scheduled', 'started'])
                ->update(['join_url' => $url, 'meeting_id' => basename(parse_url($url, PHP_URL_PATH))]);
        });
        return redirect()->route('meetings.manage')->with('success', 'Tautan tetap Google Meet PTSP disimpan. Pengguna dapat menjadwalkan konsultasi.');
    }
}
