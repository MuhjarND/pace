<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ChatMessage;
use App\Meeting;
use App\CallLog;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function fetch()
    {
        $user = Auth::user();
        if (!$user)
            return response()->json(['count' => 0, 'notifications' => []]);

        // Only staff see notifications
        if ($user->isAdmin() || $user->isOperator()) {
            $chatQuery = ChatMessage::with('sender')
                ->where('is_read', false)
                ->whereHas('chatRoom', function ($query) use ($user) {
                    $query->accessibleTo($user)
                        ->whereColumn('chat_rooms.user_id', 'chat_messages.sender_id');
                });

            $meetingQuery = Meeting::accessibleTo($user)->with('user')
                ->where('status', 'scheduled')
                ->where('verification_status', 'pending');

            // Unread Chats
            // Count unique chat rooms with unread messages from OTHERS
            $unreadChatsCount = (clone $chatQuery)
                ->distinct('chat_room_id')
                ->count('chat_room_id');

            // Pending Meetings (Scheduled for future)
            $pendingMeetings = (clone $meetingQuery)->count();

            // Recent actual notifications (limit 5)
            // 1. New Messages
            $recentMessages = (clone $chatQuery)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get()
                ->map(function ($msg) {
                return [
                'type' => 'chat',
                'title' => 'Pesan Baru',
                'message' => 'Dari: ' . ($msg->sender->name ?? 'Guest'),
                'time' => $msg->created_at->diffForHumans(),
                'url' => route('chat.show', $msg->chat_room_id),
                'sort_key' => $msg->created_at->timestamp,
                ];
            });

            // 2. New Meetings
            $recentMeetings = (clone $meetingQuery)
                ->orderBy('created_at', 'desc')
                ->take(3)
                ->get()
                ->map(function ($meeting) {
                return [
                'type' => 'meeting',
                'title' => 'Konsultasi Menunggu Verifikasi',
                'message' => 'Topik: ' . $meeting->topic,
                'time' => $meeting->created_at->diffForHumans(),
                'url' => route('meetings.index'),
                'sort_key' => $meeting->created_at->timestamp,
                ];
            });

            $allNotifications = collect($recentMessages->all())->merge($recentMeetings->all())
                ->sortByDesc('sort_key')
                ->take(5)
                ->map(function ($item) {
                    unset($item['sort_key']);
                    return $item;
                })->values();

            return response()->json([
                'success' => true,
                'unread_chats' => $unreadChatsCount,
                'unread_meetings' => $pendingMeetings,
                'total_count' => $unreadChatsCount + $pendingMeetings,
                'notifications' => $allNotifications
            ]);
        }

        return response()->json(['count' => 0, 'notifications' => []]);
    }
}
