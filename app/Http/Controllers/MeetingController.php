<?php

namespace App\Http\Controllers;

use App\ConsultationSetting;
use App\Meeting;
use App\Rules\GoogleMeetUrl;
use App\Services\ConsultationQueueService;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MeetingController extends Controller
{
    private $queues;
    public function __construct(ConsultationQueueService $queues)
    {
        $this->middleware('auth');
        $this->queues = $queues;
    }

    public function index()
    {
        $meetings = Meeting::accessibleTo(auth()->user())->with(['user', 'admin', 'guestTicket.notification'])->orderBy('start_time', 'desc')->get();
        $currentQueue = $this->queues->waiting(auth()->user()->work_unit);
        return view('meetings.index', compact('meetings', 'currentQueue'));
    }

    public function create()
    {
        $user = auth()->user();
        if ($user->isAdmin() || $user->isOperator()) return redirect()->route('meetings.manage');
        $setting = ConsultationSetting::where('work_unit', $user->work_unit)->first();
        $localNow = now()->timezone(config('ptsp.timezone'));
        $queue = $this->queues->waiting($user->work_unit);
        $unit = \App\WorkUnit::where('name', $user->work_unit)->firstOrFail();
        $selectedDate = old('consultation_date', $localNow->toDateString());
        $validDate = \Illuminate\Support\Facades\Validator::make(['date' => $selectedDate], ['date' => 'required|date_format:Y-m-d'])->passes();
        $availabilityDate = $validDate ? Carbon::createFromFormat('!Y-m-d', $selectedDate, config('ptsp.timezone')) : $localNow;
        $availability = app(\App\Services\ServiceHoursService::class)->availability($unit, $availabilityDate);
        return view('meetings.create', compact('setting', 'localNow', 'queue', 'availability'));
    }

    public function availability(Request $request)
    {
        abort_if(auth()->user()->isAdmin() || auth()->user()->isOperator(), 403);
        $data = $request->validate(['date' => 'required|date_format:Y-m-d']);
        $unit = \App\WorkUnit::where('name', auth()->user()->work_unit)->firstOrFail();
        $date = Carbon::createFromFormat('!Y-m-d', $data['date'], config('ptsp.timezone'));
        return response()->json(app(\App\Services\ServiceHoursService::class)->availability($unit, $date))->header('Cache-Control', 'no-store');
    }

    private function actionResult($message)
    {
        return redirect()->route(request()->boolean('queue_return') ? 'operations.queue' : 'meetings.index')->with('success', $message);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        abort_if($user->isAdmin() || $user->isOperator(), 403);
        $data = $request->validate(['topic' => 'required|string|max:255',
            'consultation_date' => 'required|date_format:Y-m-d', 'consultation_time' => ['required', 'regex:/^(now|([01][0-9]|2[0-3]):[0-5][0-9])$/']]);
        $localNow = now()->timezone(config('ptsp.timezone'));
        $date = Carbon::createFromFormat('!Y-m-d', $data['consultation_date'], config('ptsp.timezone'));
        if ($date->lt($localNow->copy()->startOfDay()) || $date->gt($localNow->copy()->startOfDay()->addDays(config('ptsp.consultation_booking_days')))
            || ($data['consultation_time'] === 'now' && !$date->isSameDay($localNow))) {
            throw ValidationException::withMessages(['consultation_date' => 'Pilih tanggal yang tersedia. Pilihan sekarang hanya untuk hari ini.']);
        }
        $start = $data['consultation_time'] === 'now' ? now()->copy()
            : Carbon::createFromFormat('Y-m-d H:i', $data['consultation_date'].' '.$data['consultation_time'], config('ptsp.timezone'))->startOfMinute()->utc();
        if ($data['consultation_time'] !== 'now' && $start->lt(now())) {
            throw ValidationException::withMessages(['consultation_time' => 'Waktu sudah lewat. Pilih Sekarang atau waktu berikutnya.']);
        }
        $meeting = DB::transaction(function () use ($user, $data, $start) {
            $unit = \App\WorkUnit::where('name', $user->work_unit)->lockForUpdate()->firstOrFail();
            $setting = ConsultationSetting::where('work_unit', $user->work_unit)->lockForUpdate()->first();
            if (!$setting || !GoogleMeetUrl::normalize($setting->join_url)) {
                throw ValidationException::withMessages(['consultation_date' => 'PTSP satker Anda belum menyimpan tautan Google Meet.']);
            }
            // Reuse an active request for the same user and time; concurrent submissions share this lock.
            $existing = Meeting::where('user_id', $user->id)->where('work_unit', $user->work_unit)
                ->whereIn('status', ['scheduled', 'started']);
            if ($data['consultation_time'] === 'now') {
                $existing->where('start_time', '<=', now());
            } else {
                $existing->where('start_time', $start);
            }
            if ($duplicate = $existing->orderBy('id')->first()) return $duplicate;
            app(\App\Services\ServiceHoursService::class)->assertBookable($unit, $start, $data['consultation_time'] === 'now');
            $queueDate = $start->copy()->timezone(config('ptsp.timezone'))->format('Y-m-d');
            $number = (int) Meeting::where('work_unit', $user->work_unit)->where('queue_date', $queueDate)->max('queue_number') + 1;
            $staff = User::where('is_active', true)->whereIn('role', ['admin', 'operator'])->where('work_unit', $user->work_unit)
                ->orderByRaw("CASE WHEN role = 'operator' THEN 0 ELSE 1 END")->orderBy('id')->first();
            $url = GoogleMeetUrl::normalize($setting->join_url);
            return Meeting::create(['user_id' => $user->id, 'admin_id' => optional($staff)->id, 'work_unit' => $user->work_unit,
                'topic' => $data['topic'], 'start_time' => $start, 'duration' => config('ptsp.consultation_duration_minutes'),
                'join_url' => $url, 'meeting_id' => basename(parse_url($url, PHP_URL_PATH)), 'status' => 'scheduled',
                'verification_status' => $start->lte(now()) ? 'immediate' : 'pending', 'queue_date' => $queueDate, 'queue_number' => $number]);
        });
        if ($meeting->canJoin($user)) return $this->openMeeting($meeting->id, true);
        return redirect()->route('meetings.index')->with('success', 'Jadwal konsultasi tersimpan. Nomor antrean Anda '.$meeting->queueLabel().'.');
    }

    private function locked($id, callable $callback)
    {
        $visible = Meeting::accessibleTo(auth()->user())->findOrFail($id);
        return DB::transaction(function () use ($visible, $callback) {
            \App\WorkUnit::where('name', $visible->work_unit)->lockForUpdate()->firstOrFail();
            ConsultationSetting::where('work_unit', $visible->work_unit)->lockForUpdate()->first();
            $meeting = Meeting::whereKey($visible->id)->lockForUpdate()->firstOrFail();
            return $callback($meeting);
        });
    }

    public function join($id)
    {
        return $this->openMeeting($id);
    }

    private function openMeeting($id, $redirectWaiting = false)
    {
        return $this->locked($id, function ($meeting) use ($redirectWaiting) {
            abort_unless($meeting->canJoin(auth()->user()), 403, 'Konsultasi belum diverifikasi, belum waktunya, atau sudah selesai.');
            $queueState = $this->queues->state($meeting);
            if ($queueState['ahead'] > 0) {
                return $redirectWaiting ? redirect()->route('meetings.join', $meeting->id)
                    : view('meetings.queue', compact('meeting', 'queueState'));
            }
            return redirect()->away($meeting->join_url);
        });
    }

    public function queue($id)
    {
        $meeting = Meeting::accessibleTo(auth()->user())->findOrFail($id);
        abort_unless((int) $meeting->user_id === (int) auth()->id() || $meeting->canManage(auth()->user()), 403);
        return response()->json($this->queues->state($meeting));
    }

    public function enter($id)
    {
        return $this->locked($id, function ($meeting) {
            abort_unless($meeting->canJoin(auth()->user()), 403);
            return redirect()->away($meeting->join_url);
        });
    }

    public function start($id)
    {
        return $this->locked($id, function ($meeting) {
            abort_unless($meeting->canManage(auth()->user()), 403);
            abort_unless($meeting->canJoin(auth()->user()) && $this->queues->state($meeting)['ahead'] === 0, 422, 'Selesaikan antrean sebelumnya terlebih dahulu.');
            abort_if($meeting->guestTicket && !$meeting->called_at, 422, 'Panggil tamu sebelum memulai layanan.');
            $meeting->markStarted();
            return $this->actionResult('Konsultasi sedang dilayani.');
        });
    }

    public function verify(Request $request, $id)
    {
        $data = $request->validate(['decision' => 'required|in:verified,rejected', 'verification_note' => 'nullable|string|max:1000']);
        return $this->locked($id, function ($meeting) use ($data) {
            abort_unless($meeting->canManage(auth()->user()), 403);
            abort_unless($meeting->status === 'scheduled' && $meeting->verification_status === 'pending', 422);
            $meeting->update(['verification_status' => $data['decision'], 'verified_by' => auth()->id(), 'verified_at' => now(),
                'verification_note' => $data['verification_note'] ?? null, 'status' => $data['decision'] === 'rejected' ? 'cancelled' : 'scheduled']);
            return $this->actionResult('Verifikasi konsultasi disimpan.');
        });
    }

    public function complete($id)
    {
        return $this->locked($id, function ($meeting) {
            abort_unless($meeting->canManage(auth()->user()), 403);
            abort_unless($meeting->canJoin(auth()->user()) && $this->queues->state($meeting)['ahead'] === 0, 422, 'Antrean ini belum dapat diselesaikan.');
            abort_if($meeting->guestTicket && $meeting->status !== 'started', 422, 'Mulai layanan sebelum menandai selesai.');
            $meeting->update(['status' => 'ended', 'completed_at' => now()]);
            return $this->actionResult('Konsultasi selesai. Antrean berikutnya dapat dilayani.');
        });
    }

    public function destroy($id)
    {
        return $this->locked($id, function ($meeting) {
            abort_unless((int) $meeting->user_id === (int) auth()->id() || $meeting->canManage(auth()->user()), 403);
            abort_unless(in_array($meeting->status, ['scheduled', 'started'], true), 422);
            $meeting->update(['status' => 'cancelled']);
            return $this->actionResult('Jadwal konsultasi dibatalkan.');
        });
    }
}
