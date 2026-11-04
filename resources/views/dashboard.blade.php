@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
@php($isStaff = auth()->user()->isAdmin() || auth()->user()->isOperator())
@include('partials.page-heading', [
    'eyebrow' => $isStaff ? 'Ruang kerja PTSP' : 'Ruang konsultasi Anda',
    'heading' => 'Selamat datang, ' . explode(' ', auth()->user()->name)[0] . '.',
    'description' => $isStaff ? 'Kelola percakapan dan konsultasi masyarakat dalam satu tempat.' : 'Pilih layanan dan terhubung dengan petugas PTSP satuan kerja Anda.',
    'aside' => now()->timezone(config('ptsp.timezone', 'Asia/Jayapura'))->locale('id')->translatedFormat('l, d F Y') . ' · WIT',
    'illustration' => 'consultation',
])
<div class="satker-ribbon">
    <span class="satker-ribbon-icon"><i class="fas fa-landmark" aria-hidden="true"></i></span>
    <div><span class="eyebrow">Satuan kerja Anda</span><strong>{{ auth()->user()->work_unit ?: 'Satuan kerja belum dipilih' }}</strong></div>
    <span class="satker-ribbon-label">{{ $isStaff ? 'Area petugas' : 'Layanan masyarakat' }}</span>
</div>
<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card primary animate__animated animate__fadeInUp" style="animation-delay: 0.1s">
        <div class="stat-icon"><i class="fas fa-concierge-bell"></i></div>
        <div class="stat-value" data-count="{{ $totalServices }}">{{ $totalServices }}</div>
        <div class="stat-label">Layanan Aktif</div>
    </div>
    <div class="stat-card secondary animate__animated animate__fadeInUp" style="animation-delay: 0.2s">
        <div class="stat-icon"><i class="fas fa-comments"></i></div>
        <div class="stat-value" data-count="{{ $totalChats }}">{{ $totalChats }}</div>
        <div class="stat-label">Chat Aktif</div>
    </div>
    <div class="stat-card green animate__animated animate__fadeInUp" style="animation-delay: 0.3s">
        <div class="stat-icon"><i class="fas fa-video"></i></div>
        <div class="stat-value" data-count="{{ $totalMeetings }}">{{ $totalMeetings }}</div>
        <div class="stat-label">Meeting Terjadwal</div>
    </div>
    <div class="stat-card orange animate__animated animate__fadeInUp" style="animation-delay: 0.4s">
        <div class="stat-icon"><i class="fas fa-headset"></i></div>
        <div class="stat-value" data-count="{{ $totalCalls }}">{{ $totalCalls }}</div>
        <div class="stat-label">Total Panggilan</div>
    </div>
</div>

@if($isMonitoringAdmin)
<div class="glass-card" style="margin-bottom: 20px;">
    <div class="section-header">
        <h3><i class="fas fa-sitemap" style="color: var(--secondary); margin-right: 8px;"></i> Monitoring Satuan Kerja</h3>
        <div style="display: flex; align-items: center; gap: 10px;">
            <form method="GET" action="{{ route('dashboard') }}" style="display: flex; align-items: center; gap: 8px;">
                <select aria-label="Filter satuan kerja" name="work_unit" class="form-control" style="min-width: 240px;">
                    <option value="">Semua Satuan Kerja</option>
                    @foreach($monitoringWorkUnits as $unit)
                        <option value="{{ $unit }}" {{ $selectedMonitoringUnit === $unit ? 'selected' : '' }}>{{ $unit }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm btn-secondary">
                    <i class="fas fa-filter"></i> Filter
                </button>
                @if($selectedMonitoringUnit)
                <a href="{{ route('dashboard') }}" class="btn btn-sm btn-secondary">
                    <i class="fas fa-rotate-left"></i> Reset
                </a>
                @endif
            </form>
            <span class="badge badge-info">{{ $satkerMonitoring->count() }} Akun</span>
        </div>
    </div>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Satuan Kerja</th>
                    <th>Akun</th>
                    <th>Status</th>
                    <th>Chat Terbuka</th>
                    <th>Meeting Terjadwal</th>
                    <th>Panggilan Hari Ini</th>
                </tr>
            </thead>
            <tbody>
                @forelse($satkerMonitoring as $item)
                <tr>
                    <td>{{ $item->work_unit }}</td>
                    <td>{{ $item->name }}</td>
                    <td>
                        <span class="badge badge-{{ $item->is_online ? 'success' : 'danger' }}">
                            {{ $item->is_online ? 'Online' : 'Offline' }}
                        </span>
                    </td>
                    <td>{{ $item->open_chats }}</td>
                    <td>{{ $item->scheduled_meetings }}</td>
                    <td>{{ $item->calls_today }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--text-muted);">Tidak ada data untuk filter satker yang dipilih.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="grid-2 dashboard-workspace">
    <section class="glass-card channel-panel">
        <div class="section-header"><div><p class="eyebrow">Kanal pelayanan</p><h2>Mulai dari sini</h2></div><span class="section-index" aria-hidden="true"><i class="fas fa-arrow-turn-down"></i></span></div>
        <div class="channel-grid">
            <a href="{{ route('chat.index') }}" class="channel-card channel-purple">
                <span class="channel-icon"><i class="fas fa-comments" aria-hidden="true"></i></span>
                <span><strong>{{ $isStaff ? 'Kotak masuk PTSP' : 'Chat PTSP satker' }}</strong><small>{{ $isStaff ? 'Baca dan balas percakapan' : 'Tanyakan kebutuhan layanan' }}</small></span><i class="fas fa-arrow-up-right-from-square channel-arrow" aria-hidden="true"></i>
            </a>
            <a href="{{ $isStaff ? route('meetings.index') : route('meetings.create') }}" class="channel-card channel-green">
                <span class="channel-icon"><i class="fas fa-video" aria-hidden="true"></i></span>
                <span><strong>Google Meet</strong><small>{{ $isStaff ? 'Kelola jadwal dan antrean' : 'Pilih jadwal konsultasi' }}</small></span><i class="fas fa-arrow-up-right-from-square channel-arrow" aria-hidden="true"></i>
            </a>
            <a href="{{ route('chat.voice') }}" class="channel-card channel-blue">
                <span class="channel-icon"><i class="fas fa-microphone-lines" aria-hidden="true"></i></span>
                <span><strong>Voice to Chat</strong><small>Bicara untuk menulis pesan</small></span><i class="fas fa-arrow-up-right-from-square channel-arrow" aria-hidden="true"></i>
            </a>
            <a href="{{ route('call-center.index') }}" class="channel-card channel-orange">
                <span class="channel-icon"><i class="fas fa-headset" aria-hidden="true"></i></span>
                <span><strong>Call Center</strong><small>Buka layanan panggilan</small></span><i class="fas fa-arrow-up-right-from-square channel-arrow" aria-hidden="true"></i>
            </a>
        </div>
    </section>

    <!-- Upcoming Meetings -->
    <div class="glass-card">
        <div class="section-header">
            <div><p class="eyebrow">Agenda konsultasi</p><h2>Jadwal mendatang</h2></div>
            <a href="{{ route('meetings.index') }}" class="btn btn-sm btn-secondary">Lihat Semua</a>
        </div>
        @forelse($upcomingMeetings as $meeting)
        <div style="display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--glass-border);">
            <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(var(--secondary-rgb), 0.15); display: flex; align-items: center; justify-content: center; color: var(--secondary);">
                <i class="fas fa-video"></i>
            </div>
            <div style="flex: 1;">
                <div style="font-size: 14px; font-weight: 500;">{{ $meeting->topic }}</div>
                <div style="font-size: 12px; color: var(--text-muted);">{{ $meeting->localStart()->format('d M Y, H:i') }} WIT · {{ $meeting->verificationLabel() }}</div>
            </div>
            <span class="badge badge-info">{{ $meeting->duration }} min</span>
            @if($meeting->canJoin(auth()->user()))
            <a href="{{ route('meetings.join', $meeting->id) }}" class="btn btn-success btn-sm" target="_blank" rel="noopener noreferrer" aria-label="Gabung meeting {{ $meeting->topic }}">
                <i class="fas fa-sign-in-alt"></i> Gabung
            </a>
            @endif
        </div>
        @empty
        <div class="empty-state" style="padding: 30px;">
            <i class="fas fa-calendar-check" style="font-size: 36px;"></i>
            <h3>Belum ada jadwal konsultasi</h3><p>{{ $isStaff ? 'Pengajuan masyarakat akan tampil di sini.' : 'Tentukan waktu yang sesuai untuk berkonsultasi.' }}</p><a href="{{ $isStaff ? route('meetings.manage') : route('meetings.create') }}" class="btn btn-secondary btn-sm">{{ $isStaff ? 'Atur tautan Google Meet' : 'Jadwalkan konsultasi' }} <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        </div>
        @endforelse
    </div>
</div>

<!-- Services -->
<div class="glass-card" style="margin-top: 20px;">
    <div class="section-header">
        <div><p class="eyebrow">Informasi dan bantuan</p><h2>Jelajahi layanan PTSP</h2></div>
        <a href="{{ route('services.index') }}" class="btn btn-sm btn-secondary">Semua Layanan</a>
    </div>
    <div class="grid-4">
        @foreach($services->unique('category')->take(4) as $service)
        <div class="service-card" data-category="{{ $service->category }}">
            <div class="service-icon" aria-hidden="true">
                <i class="fas {{ $service->icon }}"></i>
            </div>
            <h3>{{ $service->name }}</h3>
            <p>{{ Str::limit($service->description, 100) }}</p><a class="service-detail-link" href="{{ route('services.index', ['category' => $service->category]) }}">Lihat layanan <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        </div>
        @endforeach
    </div>
</div>
@endsection
