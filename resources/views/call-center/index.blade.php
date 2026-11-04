@extends('layouts.app')
@section('title', 'Call Center')
@section('page-title', 'Call Center')

@section('content')
@include('partials.page-heading', ['heading'=>'Call Center', 'description'=>'Hubungi petugas PTSP dan lihat riwayat panggilan layanan.'])

@if($isPublicUser)
<div class="glass-card">
    <div style="text-align: center; margin-bottom: 24px;">
        <h3 style="font-size: 20px; margin-bottom: 10px;">Nomor Petugas PTSP</h3>
        <p style="font-size: 14px; color: var(--text-muted); max-width: 640px; margin: 0 auto;">
            Pilih salah satu nomor petugas PTSP. Saat diklik, Anda akan diarahkan ke aplikasi telepon untuk menghubungi petugas.
        </p>
    </div>

    <div class="contact-list" style="max-width: 760px; margin: 0 auto;">
        @forelse($ptspContacts as $contact)
            @php
                $dialNumber = preg_replace('/[^0-9+]/', '', $contact->phone ?? '');
            @endphp
            <a href="tel:{{ $dialNumber }}" class="contact-item" style="text-decoration: none; color: inherit; border: 1px solid var(--glass-border); border-radius: 12px; margin-bottom: 10px; padding: 14px;">
                <div class="chat-item-avatar" style="width: 42px; height: 42px; font-size: 14px;">
                    {{ substr($contact->name, 0, 1) }}
                    @if($contact->is_online)<div class="online-dot"></div>@endif
                </div>
                <div style="flex: 1;">
                    <div style="font-size: 14px; font-weight: 600;">{{ $contact->name }}</div>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                        {{ $contact->role === 'admin' ? 'Admin PTSP' : 'Operator PTSP' }}
                    </div>
                    <div style="font-size: 13px; color: var(--primary-light); margin-top: 4px;">
                        <i class="fas fa-phone-alt" style="margin-right: 6px;"></i>{{ $contact->phone }}
                    </div>
                </div>
                <span class="btn btn-sm btn-success">
                    <i class="fas fa-phone"></i> Hubungi
                </span>
            </a>
        @empty
            <div class="empty-state" style="padding: 30px;">
                <i class="fas fa-phone-slash" style="font-size: 30px; margin-bottom: 12px;"></i>
                <p style="margin: 0;">Nomor petugas PTSP belum tersedia.</p>
            </div>
        @endforelse
    </div>
</div>
@else
<div class="call-container">
    <div class="glass-card">
        <div style="text-align: center; margin-bottom: 20px;">
            <h3 style="font-size: 18px; margin-bottom: 8px;">Panggilan Langsung</h3>
            <p style="font-size: 13px; color: var(--text-muted);">Hubungi petugas PTSP secara langsung</p>
        </div>

        <div class="dial-display" id="dialDisplay">Masukkan Nomor</div>

        <div class="dial-pad">
            <button class="dial-btn" data-digit="1">1</button>
            <button class="dial-btn" data-digit="2">2<span>ABC</span></button>
            <button class="dial-btn" data-digit="3">3<span>DEF</span></button>
            <button class="dial-btn" data-digit="4">4<span>GHI</span></button>
            <button class="dial-btn" data-digit="5">5<span>JKL</span></button>
            <button class="dial-btn" data-digit="6">6<span>MNO</span></button>
            <button class="dial-btn" data-digit="7">7<span>PQRS</span></button>
            <button class="dial-btn" data-digit="8">8<span>TUV</span></button>
            <button class="dial-btn" data-digit="9">9<span>WXYZ</span></button>
            <button class="dial-btn" data-digit="*">*</button>
            <button class="dial-btn" data-digit="0">0<span>+</span></button>
            <button class="dial-btn" id="dialClear"><i class="fas fa-backspace"></i></button>
        </div>

        <div style="display: flex; justify-content: center; gap: 16px; margin-top: 20px;">
            <button class="dial-btn call" id="startCallBtn" style="width: 65px; height: 65px;">
                <i class="fas fa-phone"></i>
            </button>
        </div>

        <div style="margin-top: 30px;">
            <h4 style="font-size: 15px; margin-bottom: 12px;">
                <i class="fas fa-history" style="margin-right: 6px; color: var(--text-muted);"></i> Riwayat Panggilan
            </h4>
            @forelse($callHistory as $call)
            <div style="display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--glass-border);">
                <div style="width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
                    background: {{ $call->status === 'answered' || $call->status === 'ended' ? 'rgba(16,185,129,0.15)' : 'rgba(239,68,68,0.15)' }};
                    color: {{ $call->status === 'answered' || $call->status === 'ended' ? 'var(--success)' : 'var(--danger)' }};">
                    <i class="fas {{ $call->status === 'missed' ? 'fa-phone-slash' : ($call->type === 'video' ? 'fa-video' : 'fa-phone') }}"></i>
                </div>
                <div style="flex: 1;">
                    <div style="font-size: 14px; font-weight: 500;">
                        {{ $call->caller_id == auth()->id() ? ($call->receiver->name ?? '-') : ($call->caller->name ?? '-') }}
                    </div>
                    <div style="font-size: 12px; color: var(--text-muted);">
                        {{ $call->created_at->format('d M H:i') }} - {{ $call->duration > 0 ? gmdate('i:s', $call->duration) : '-' }}
                    </div>
                </div>
                <span class="badge badge-{{ $call->status === 'ended' ? 'success' : ($call->status === 'missed' ? 'danger' : 'info') }}">
                    {{ ucfirst($call->status) }}
                </span>
            </div>
            @empty
            <p style="font-size: 13px; color: var(--text-muted); text-align: center; padding: 20px;">Belum ada riwayat panggilan</p>
            @endforelse
        </div>
    </div>

    <div class="glass-card" style="align-self: start;">
        <h4 style="font-size: 15px; margin-bottom: 16px;">
            <i class="fas fa-users" style="margin-right: 6px; color: var(--primary-light);"></i> Kontak
        </h4>
        <div class="contact-list">
            @foreach($contacts as $contact)
            <div class="contact-item">
                <div class="chat-item-avatar" style="width: 38px; height: 38px; font-size: 13px;">
                    {{ substr($contact->name, 0, 1) }}
                    @if($contact->is_online)<div class="online-dot"></div>@endif
                </div>
                <div style="flex: 1;">
                    <div style="font-size: 14px; font-weight: 500;">{{ $contact->name }}</div>
                    <div style="font-size: 12px; color: var(--text-muted);">{{ $contact->role }}</div>
                </div>
                <button class="btn btn-sm btn-success contact-call-btn" data-user-id="{{ $contact->id }}" data-user-name="{{ $contact->name }}">
                    <i class="fas fa-phone"></i>
                </button>
            </div>
            @endforeach
        </div>
    </div>
</div>

<div class="call-overlay" id="callOverlay">
    <div class="call-avatar-lg">
        <i class="fas fa-user"></i>
    </div>
    <h2 class="call-name" style="font-size: 24px; margin-bottom: 8px;">-</h2>
    <p class="call-status" style="color: var(--text-muted); font-size: 16px;">Memanggil...</p>
    <p class="call-timer" style="font-size: 20px; font-weight: 300; margin-top: 10px; letter-spacing: 2px;">00:00</p>
    <div class="call-actions">
        <button class="call-action-btn mute" id="muteBtn"><i class="fas fa-microphone"></i></button>
        <button class="call-action-btn end-call" id="endCallBtn"><i class="fas fa-phone-slash"></i></button>
        <button class="call-action-btn speaker"><i class="fas fa-volume-up"></i></button>
    </div>
</div>
@endif
@endsection

@section('scripts')
@if(!$isPublicUser)
<script src="{{ asset('js/call.js') }}"></script>
@endif
@endsection
