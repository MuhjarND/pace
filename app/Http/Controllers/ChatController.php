<?php

namespace App\Http\Controllers;

use App\ChatRoom;
use App\ChatMessage;
use App\Services\ChatRoutingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChatController extends Controller
{
    private $routing;

    public function __construct(ChatRoutingService $routing)
    {
        $this->middleware('auth');
        $this->routing = $routing;
    }

    public function index()
    {
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->isOperator()) {
            if (!in_array($user->work_unit, \App\WorkUnit::knownNames(), true)) {
                return redirect()->route('guest.welcome')->withErrors([
                    'work_unit' => 'Pilih satuan kerja terlebih dahulu agar chat diterima PTSP yang tepat.',
                ]);
            }

            return redirect()->route('chat.show', $this->routing->openFor($user)->id);
        }

        $chatRooms = ChatRoom::accessibleTo($user)->with(['user', 'admin', 'service', 'latestMessage'])
            ->orderBy('updated_at', 'desc')->get();

        return view('chat.index', compact('chatRooms'));
    }

    public function voice(Request $request)
    {
        $user = auth()->user();
        if ($user->isAdmin() || $user->isOperator()) {
            $chatRooms = ChatRoom::where('work_unit', $user->work_unit)->whereNotNull('work_unit')
                ->where('status', 'open')->with(['user', 'service'])->orderBy('updated_at', 'desc')->get();
            $chatRoom = $request->filled('room_id')
                ? $chatRooms->firstWhere('id', (int) $request->query('room_id'))
                : $chatRooms->first();
            abort_if($request->filled('room_id') && !$chatRoom, 404);

            return view('chat.voice', compact('chatRoom', 'chatRooms'));
        }
        if (!in_array($user->work_unit, \App\WorkUnit::knownNames(), true)) {
            return redirect()->route('chat.index');
        }

        $chatRoom = $this->routing->openFor($user);

        return view('chat.voice', compact('chatRoom'));
    }

    public function show($id)
    {
        $chatRoom = $this->accessibleRoom($id)->load(['user', 'admin', 'service']);
        $this->markMessagesRead($chatRoom);
        $messages = ChatMessage::with('sender')->where('chat_room_id', $id)
            ->orderBy('id')->get();
        $canReply = $chatRoom->canReply(auth()->user());

        return view('chat.show', compact('chatRoom', 'messages', 'canReply'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'chat_room_id' => 'required|integer|exists:chat_rooms,id',
            'message' => 'required|string|max:5000',
            'type' => 'sometimes|in:text,voice,file',
        ]);

        $chatRoom = $this->accessibleRoom($data['chat_room_id']);
        abort_unless($chatRoom->canReply(auth()->user()), 403, 'Pesan hanya dapat dilayani PTSP satker percakapan ini.');

        $message = ChatMessage::create([
            'chat_room_id' => $chatRoom->id,
            'sender_id' => auth()->id(),
            'message' => $data['message'],
            'type' => $data['type'] ?? 'text',
        ]);

        if (!$chatRoom->admin_id && (auth()->user()->isAdmin() || auth()->user()->isOperator())) {
            ChatRoom::whereKey($chatRoom->id)->whereNull('admin_id')->update(['admin_id' => auth()->id()]);
        }
        $chatRoom->touch();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $message->load('sender')]);
        }

        return redirect()->route('chat.show', $chatRoom->id);
    }

    public function createRoom(Request $request)
    {
        $data = $request->validate([
            'service_id' => ['nullable', Rule::exists('services', 'id')->where('is_active', true)],
        ]);
        if (!empty($data['service_id']) && \App\Service::findOrFail($data['service_id'])->isComplaintChannel()) {
            return redirect()->route('public.complaints.create');
        }
        $chatRoom = $this->routing->openFor(auth()->user(), $data['service_id'] ?? null);

        return redirect()->route('chat.show', $chatRoom->id);
    }

    public function fetchMessages(Request $request, $id)
    {
        $data = $request->validate(['last_id' => 'sometimes|integer|min:0']);
        $chatRoom = $this->accessibleRoom($id);
        $messages = ChatMessage::with('sender')->where('chat_room_id', $id)
            ->where('id', '>', $data['last_id'] ?? 0)->orderBy('id')->get();
        $this->markMessagesRead($chatRoom);

        return response()->json(['messages' => $messages]);
    }

    private function accessibleRoom($id)
    {
        return ChatRoom::accessibleTo(auth()->user())->findOrFail($id);
    }

    private function markMessagesRead(ChatRoom $room)
    {
        $user = auth()->user();
        $query = ChatMessage::where('chat_room_id', $room->id)->where('is_read', false);

        if ((int) $room->user_id === (int) $user->id) {
            $query->where('sender_id', '!=', $user->id);
        } elseif (($user->isAdmin() || $user->isOperator()) && $user->work_unit === $room->work_unit) {
            $query->where('sender_id', $room->user_id);
        } else {
            return;
        }

        $query->update(['is_read' => true]);
    }
}
