<?php

namespace App\Http\Controllers;

use App\CallLog;
use App\User;
use Illuminate\Http\Request;

class CallCenterController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = auth()->user();
        $isPublicUser = !$user->isAdmin() && !$user->isOperator();

        if ($isPublicUser) {
            $ptspContactsQuery = User::where('is_active', true)->whereIn('role', ['admin', 'operator'])
                ->whereNotNull('phone')
                ->where('phone', '!=', '')
                ->orderByRaw("CASE WHEN role = 'operator' THEN 0 ELSE 1 END")
                ->orderBy('name')
                ->orderBy('name');

            if ($user->work_unit) {
                $ptspContactsQuery->where('work_unit', $user->work_unit);
            }
            else {
                $ptspContactsQuery->where('can_monitor', true);
            }

            $ptspContacts = $ptspContactsQuery->get();

            return view('call-center.index', compact('isPublicUser', 'ptspContacts'));
        }

        if ($user->canMonitor()) {
            $contacts = User::where('is_active', true)->where('id', '!=', $user->id)->get();
        }
        else {
            $contacts = User::where('is_active', true)->where('id', '!=', $user->id)
                ->where('work_unit', $user->work_unit)
                ->get();
        }

        if ($user->canMonitor()) {
            $callHistory = CallLog::with(['caller', 'receiver'])
                ->orderBy('created_at', 'desc')
                ->take(20)->get();
        }
        else {
            $callHistory = CallLog::with(['caller', 'receiver'])
                ->where('caller_id', $user->id)
                ->orWhere('receiver_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->take(20)->get();
        }

        $ptspContacts = collect();

        return view('call-center.index', compact('isPublicUser', 'ptspContacts', 'contacts', 'callHistory'));
    }

    public function call(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'type' => 'in:audio,video',
        ]);

        $receiver = User::where('is_active', true)->findOrFail($request->receiver_id);
        $actor = auth()->user();
        abort_unless($receiver->id !== $actor->id && $actor->work_unit && $receiver->work_unit === $actor->work_unit
            && ($actor->isAdmin() || $actor->isOperator() || $receiver->isAdmin() || $receiver->isOperator()), 403);

        $callLog = CallLog::create([
            'caller_id' => auth()->id(),
            'receiver_id' => $request->receiver_id,
            'type' => $request->type ?? 'audio',
            'status' => 'ongoing',
        ]);

        return response()->json([
            'success' => true,
            'call' => $callLog->load(['caller', 'receiver']),
        ]);
    }

    public function endCall(Request $request, $id)
    {
        $request->validate(['duration'=>'nullable|integer|min:0|max:86400']);
        $callLog = CallLog::where(function($q) { $q->where('caller_id', auth()->id())->orWhere('receiver_id',auth()->id()); })->findOrFail($id);
        $callLog->update([
            'status' => 'ended',
            'duration' => $request->duration ?? 0,
        ]);

        return response()->json(['success' => true]);
    }

    public function history()
    {
        $user = auth()->user();
        $calls = CallLog::with(['caller', 'receiver'])
            ->where('caller_id', $user->id)
            ->orWhere('receiver_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($calls);
    }
}
