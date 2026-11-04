@extends('layouts.app')
@section('title', 'Chat PTSP')
@section('page-title', 'Chat PTSP')

@section('styles')
<style>.message-sender { font-size: 11px; font-weight: 600; margin-bottom: 4px; opacity: .8; }</style>
@endsection

@section('content')
@php($isStaff = auth()->user()->isAdmin() || auth()->user()->isOperator())
<div class="glass-card" style="padding: 0; display: flex; flex-direction: column; height: calc(100vh - var(--topbar-height) - 50px);">
    <div style="padding: 16px 20px; border-bottom: 1px solid var(--glass-border); display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
        <a href="{{ route($isStaff ? 'chat.index' : 'dashboard') }}" class="btn btn-sm btn-secondary" aria-label="Kembali"><i class="fas fa-arrow-left"></i></a>
        <div class="chat-item-avatar" style="width: 40px; height: 40px; font-size: 14px;">
            {{ $isStaff ? mb_substr($chatRoom->user->name ?? 'U', 0, 1) : 'PT' }}
        </div>
        <div style="flex: 1; min-width: 180px;">
            <h4 style="font-size: 15px; font-weight: 600;">
                {{ $isStaff ? ($chatRoom->user->name ?? 'Masyarakat') : 'PTSP ' . $chatRoom->work_unit }}
            </h4>
            <span style="font-size: 12px; color: var(--text-muted);">
                @if($isStaff)
                    {{ $chatRoom->work_unit }}
                @elseif($chatRoom->admin)
                    Petugas: {{ $chatRoom->admin->name }}
                @else
                    Pesan Anda menunggu petugas PTSP satker ini.
                @endif
            </span>
        </div>
        @if($chatRoom->service)<span class="badge badge-primary">{{ $chatRoom->service->name }}</span>@endif
    </div>
    <div class="voice-indicator" style="margin: 10px 20px 0;">
        <div class="voice-waves"><div class="wave"></div><div class="wave"></div><div class="wave"></div><div class="wave"></div><div class="wave"></div></div>
        <span class="voice-text-preview" style="font-size: 13px; color: var(--danger);">Mendengarkan...</span>
    </div>
    <div class="chat-messages" id="chatMessages">
        @if($messages->isEmpty())
        <p id="chatWelcome" style="text-align: center; color: var(--text-muted); padding: 20px;">
            {{ $isStaff ? 'Pendaftar ini terhubung ke PTSP satker Anda. Silakan mulai pelayanan.' : 'Sampaikan pertanyaan Anda untuk PTSP ' . $chatRoom->work_unit . '.' }}
        </p>
        @endif
        @foreach($messages as $msg)
        <div class="message-bubble {{ $msg->sender_id == auth()->id() ? 'sent' : 'received' }}" data-message-id="{{ $msg->id }}">
            <div class="message-sender">{{ $msg->sender->name ?? 'Pengguna' }}</div>
            <div style="white-space: pre-wrap; overflow-wrap: anywhere;">{{ $msg->message }}</div>
            <div class="message-time">{{ $msg->created_at->format('H:i') }}</div>
        </div>
        @endforeach
    </div>
    <p id="chatError" role="alert" style="display: none; padding: 8px 20px; color: var(--danger);"></p>
    @if($canReply)
    <div class="chat-input-area">
        <button type="button" class="chat-input-btn voice" data-voice-target="chatInput" title="Tulis pesan dengan suara" aria-label="Tulis pesan dengan suara"><i class="fas fa-microphone"></i></button>
        <input type="text" id="chatInput" aria-label="Pesan untuk percakapan PTSP" placeholder="{{ $isStaff ? 'Balas masyarakat...' : 'Kirim pesan ke PTSP satker Anda...' }}" autocomplete="off" maxlength="5000" onkeypress="if(event.key==='Enter' &amp;&amp; !event.isComposing)sendMessage()">
        <button type="button" id="chatSendButton" class="chat-input-btn send" onclick="sendMessage()" title="Kirim pesan" aria-label="Kirim pesan"><i class="fas fa-paper-plane"></i></button>
    </div>
    @else
    <p style="padding: 16px 20px; color: var(--text-muted); border-top: 1px solid var(--glass-border);">
        {{ $chatRoom->status === 'closed' ? 'Percakapan ini telah ditutup.' : 'Percakapan ini dilayani PTSP ' . $chatRoom->work_unit . '. Anda dapat melihat riwayatnya.' }}
    </p>
    @endif
</div>
@endsection

@section('scripts')
<script>
const currentUserId = {{ auth()->id() }};
const chatRoomId = {{ $chatRoom->id }};
const canReply = @json($canReply);
const fetchUrl = @json(route('chat.fetch', $chatRoom->id));
const sendUrl = @json(route('chat.store'));
const container = document.getElementById('chatMessages');
const displayedIds = new Set(Array.from(container.querySelectorAll('[data-message-id]'), el => Number(el.dataset.messageId)));
let lastMessageId = {{ $messages->last()->id ?? 0 }};
let sending = false;

function scrollToBottom() { container.scrollTop = container.scrollHeight; }
function showChatError(message) {
    const error = document.getElementById('chatError');
    error.textContent = message;
    error.style.display = message ? 'block' : 'none';
}
function appendMessage(msg) {
    const id = Number(msg.id);
    if (displayedIds.has(id)) return false;
    const welcome = document.getElementById('chatWelcome');
    if (welcome) welcome.remove();
    const bubble = document.createElement('div');
    bubble.className = 'message-bubble ' + (Number(msg.sender_id) === currentUserId ? 'sent' : 'received');
    bubble.dataset.messageId = id;
    const sender = document.createElement('div');
    sender.className = 'message-sender';
    sender.textContent = msg.sender ? msg.sender.name : 'Pengguna';
    const body = document.createElement('div');
    body.style.whiteSpace = 'pre-wrap';
    body.style.overflowWrap = 'anywhere';
    body.textContent = msg.message;
    const time = document.createElement('div');
    time.className = 'message-time';
    time.textContent = formatTime(msg.created_at);
    bubble.append(sender, body, time);
    container.appendChild(bubble);
    displayedIds.add(id);
    return true;
}
function sendMessage() {
    if (!canReply || sending) return;
    const input = document.getElementById('chatInput');
    const message = input.value.trim();
    if (!message) return;
    sending = true;
    input.disabled = true;
    document.getElementById('chatSendButton').disabled = true;
    showChatError('');
    ajaxRequest(sendUrl, 'POST', { chat_room_id: chatRoomId, message, type: 'text' }, function(err, data) {
        sending = false;
        input.disabled = false;
        document.getElementById('chatSendButton').disabled = false;
        if (err || !data || !data.message) {
            showChatError('Pesan belum terkirim. Silakan coba lagi.');
        } else {
            appendMessage(data.message);
            input.value = '';
            scrollToBottom();
        }
        input.focus();
    });
}
async function pollMessages() {
    try {
        const url = new URL(fetchUrl, window.location.href);
        url.searchParams.set('last_id', lastMessageId);
        const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
        if (!response.ok) throw new Error('Fetch failed');
        const data = await response.json();
        let added = false;
        data.messages.forEach(msg => {
            if (appendMessage(msg)) {
                added = true;
                if (Number(msg.sender_id) !== currentUserId) playNotificationSound();
            }
            lastMessageId = Math.max(lastMessageId, Number(msg.id));
        });
        if (added) scrollToBottom();
    } catch (error) {
        console.error('Chat update failed:', error);
    } finally {
        setTimeout(pollMessages, 3000);
    }
}
scrollToBottom();
setTimeout(pollMessages, 3000);
</script>
@endsection
