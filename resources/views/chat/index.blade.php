@extends('layouts.app')
@section('title', 'Chat PTSP')
@section('page-title', 'Chat PTSP')

@section('content')
@include('partials.page-heading', [
    'heading'=>auth()->user()->canMonitor() ? 'Monitoring Chat PTSP' : 'Kotak masuk PTSP',
    'description'=>auth()->user()->canMonitor() ? 'Pantau percakapan setiap satuan kerja. Pelayanan dilakukan oleh PTSP satker masing-masing.' : 'Percakapan masyarakat di '.auth()->user()->work_unit.' masuk ke kotak layanan ini.',
])
<div class="glass-card" style="padding: 0;">
    @forelse($chatRooms as $room)
    @php($unread = $room->unreadCount())
    <a href="{{ route('chat.show', $room->id) }}" class="chat-item" style="border-bottom: 1px solid var(--glass-border);">
        <div class="chat-item-avatar">
            {{ mb_substr($room->user->name ?? 'U', 0, 1) }}
        </div>
        <div class="chat-item-info">
            <h4>{{ $room->user->name ?? 'Masyarakat' }}</h4>
            <div style="font-size: 12px; color: var(--primary-light); margin-bottom: 4px;">PTSP {{ $room->work_unit ?? 'Belum dipilih' }}</div>
            <p>{{ $room->latestMessage->message ?? 'Pendaftar siap dilayani oleh PTSP satker ini.' }}</p>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
                {{ $room->service->name ?? 'Konsultasi umum' }} · {{ $room->admin->name ?? 'Menunggu petugas satker' }}
            </div>
        </div>
        <div class="chat-item-meta">
            <div class="time">{{ $room->updated_at->diffForHumans() }}</div>
            @if($unread > 0)<div class="unread">{{ $unread }}</div>@endif
        </div>
    </a>
    @empty
    <div class="empty-state">
        <i class="fas fa-comments"></i>
        <h3>Belum Ada Percakapan</h3>
        <p>Percakapan akan muncul saat masyarakat mendaftar di satuan kerja Anda.</p>
    </div>
    @endforelse
</div>
@endsection
