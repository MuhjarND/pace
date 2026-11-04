<?php

namespace App\Http\Controllers;

use App\Meeting;
use App\WorkUnit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QueueDashboardController extends Controller
{
    public function __construct() { $this->middleware('auth'); }

    private function snapshot(Request $request)
    {
        $actor = auth()->user();
        abort_unless($actor->isAdmin() || $actor->isOperator(), 403);
        $request->validate([
            'work_unit' => ['nullable', Rule::in(WorkUnit::knownNames())],
            'tab' => ['nullable', Rule::in(['active', 'pending', 'history'])],
            'date' => 'nullable|date_format:Y-m-d', 'page' => 'nullable|integer|min:1|max:100000',
        ]);
        $selected = $request->input('work_unit', $actor->work_unit);
        abort_unless($actor->canMonitor() || $selected === $actor->work_unit, 403);
        $unit = WorkUnit::where('name', $selected)->with('hours', 'holidays')->firstOrFail();
        $now = Carbon::now(config('ptsp.timezone'));
        $date = $request->input('date', $now->toDateString());
        $tab = $request->input('tab', 'active');
        $base = Meeting::accessibleTo($actor)->where('work_unit', $unit->name);
        $active = (clone $base)->whereIn('status', ['scheduled', 'started'])->whereIn('verification_status', ['immediate', 'verified'])->where('start_time', '<=', now());
        $pending = (clone $base)->where('status', 'scheduled')->where('verification_status', 'pending');
        $dayStart = $now->copy()->startOfDay()->utc();
        $summary = [
            'waiting' => (clone $active)->where('status', 'scheduled')->count(),
            'serving' => (clone $active)->where('status', 'started')->count(),
            'pending' => (clone $pending)->count(),
            'completed' => (clone $base)->where('status', 'ended')->where('completed_at', '>=', $dayStart)->where('completed_at', '<', $dayStart->copy()->addDay())->count(),
        ];
        $headId = (clone $active)->orderByRaw("CASE WHEN status = 'started' THEN 0 ELSE 1 END")->orderByRaw('CASE WHEN called_at IS NOT NULL THEN 0 ELSE 1 END')->orderBy('start_time')->orderBy('id')->value('id');
        if ($tab === 'active') {
            $query = $active->orderByRaw("CASE WHEN status = 'started' THEN 0 ELSE 1 END")->orderByRaw('CASE WHEN called_at IS NOT NULL THEN 0 ELSE 1 END')->orderBy('start_time')->orderBy('id');
        } elseif ($tab === 'pending') {
            $query = $pending->orderBy('start_time')->orderBy('id');
        } else {
            $query = (clone $base)->whereIn('status', ['ended', 'cancelled'])->where('queue_date', $date)->orderByDesc('start_time')->orderByDesc('id');
        }
        $rows = $query->with('user','guestTicket.notification')->paginate(15)->appends($request->except('page'));
        $workUnits = $actor->canMonitor() ? WorkUnit::knownNames() : [$unit->name];
        $canManage = $actor->work_unit === $unit->name;
        return compact('unit', 'now', 'date', 'tab', 'summary', 'headId', 'rows', 'workUnits', 'canManage');
    }

    public function index(Request $request)
    {
        return view('operations.queue', $this->snapshot($request));
    }

    public function data(Request $request)
    {
        $snapshot = $this->snapshot($request);
        return response()->json([
            'html' => view('operations.partials.queue-board', $snapshot)->render(),
            'updated_at' => $snapshot['now']->format('H:i:s').' WIT',
        ])->header('Cache-Control', 'no-store');
    }
}
