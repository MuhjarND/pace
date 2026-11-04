<?php

namespace App\Http\Controllers;

use App\Service;
use App\ChatRoom;
use App\Meeting;
use App\CallLog;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = Auth::user();
        $isMonitoringAdmin = $currentUser && $currentUser->canMonitor();
        $selectedMonitoringUnit = $request->query('work_unit');
        $monitoringWorkUnits = collect();

        // Public Data
        $totalServices = Service::where('is_active', true)->count();
        $totalChats = $currentUser ? ChatRoom::accessibleTo($currentUser)->where('status', 'open')->count() : 0;
        $totalMeetings = $currentUser ? Meeting::accessibleTo($currentUser)->where('status', 'scheduled')->count() : 0;
        $callQuery = CallLog::query();
        if (!$currentUser) { $callQuery->whereRaw('1 = 0'); }
        elseif (!$currentUser->canMonitor()) {
            if ($currentUser->isAdmin() || $currentUser->isOperator()) {
                $unitUserIds = User::where('work_unit', $currentUser->work_unit)->select('id');
                $callQuery->where(function ($query) use ($unitUserIds) { $query->whereIn('caller_id', $unitUserIds)->orWhereIn('receiver_id', $unitUserIds); });
            } else {
                $callQuery->where(function ($query) use ($currentUser) { $query->where('caller_id', $currentUser->id)->orWhere('receiver_id', $currentUser->id); });
            }
        }
        $totalCalls = $callQuery->count();
        $services = Service::where('is_active', true)->get();
        $onlineUsers = User::where('is_active', true)->where('is_online', true)->count();

        // Private Data (Only for logged in users)
        $recentChats = [];
        $upcomingMeetings = [];
        $satkerMonitoring = collect();

        if (Auth::check()) {
            $recentChats = ChatRoom::accessibleTo($currentUser)->with(['user', 'admin', 'service', 'latestMessage'])
                ->orderBy('updated_at', 'desc')->take(5)->get();
            $upcomingMeetings = Meeting::accessibleTo($currentUser)->with(['user', 'admin', 'slot'])
                ->whereIn('status', ['scheduled', 'started'])
                ->orderBy('start_time')->take(5)->get();

            if ($isMonitoringAdmin) {

                $monitoringWorkUnits = User::whereIn('role', ['admin', 'operator'])
                    ->whereNotNull('work_unit')
                    ->where('work_unit', '!=', '')
                    ->distinct()
                    ->orderBy('work_unit')
                    ->pluck('work_unit');

                if ($selectedMonitoringUnit && !$monitoringWorkUnits->contains($selectedMonitoringUnit)) {
                    $selectedMonitoringUnit = null;
                }

                $staffQuery = User::whereIn('role', ['admin', 'operator'])
                    ->whereNotNull('work_unit')
                    ->where('work_unit', '!=', '');

                if ($selectedMonitoringUnit) {
                    $staffQuery->where('work_unit', $selectedMonitoringUnit);
                }

                $satkerMonitoring = $staffQuery->select(['id', 'name', 'role', 'work_unit', 'is_online'])
                    ->orderBy('role')
                    ->orderBy('work_unit')
                    ->get()
                    ->map(function ($staff) {
                        return (object) [
                            'name' => $staff->name,
                            'role' => $staff->role,
                            'work_unit' => $staff->work_unit,
                            'is_online' => $staff->is_online,
                            'open_chats' => ChatRoom::where('admin_id', $staff->id)->where('status', 'open')->count(),
                            'scheduled_meetings' => Meeting::where('admin_id', $staff->id)->where('status', 'scheduled')->count(),
                            'calls_today' => CallLog::whereDate('created_at', now()->toDateString())
                                ->where(function ($query) use ($staff) {
                                    $query->where('caller_id', $staff->id)
                                        ->orWhere('receiver_id', $staff->id);
                                })->count(),
                        ];
                    });
            }
        }

        return view('dashboard', compact(
            'totalServices', 'totalChats', 'totalMeetings', 'totalCalls',
            'recentChats', 'upcomingMeetings', 'services', 'onlineUsers',
            'satkerMonitoring', 'isMonitoringAdmin', 'selectedMonitoringUnit', 'monitoringWorkUnits'
        ));
    }
}
