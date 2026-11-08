@extends('layouts.app')
@section('title', 'Jadwal konsultasi')
@section('page-title', 'Jadwal konsultasi')
@section('styles')@include('meetings.partials.styles')@endsection
@section('content')
@php($isStaff = auth()->user()->isAdmin() || auth()->user()->isOperator())
<div class="section-header page-heading"><div><p class="eyebrow">Layanan PTSP</p><h1>Jadwal konsultasi</h1></div>
    <a href="{{ route($isStaff ? 'meetings.manage' : 'meetings.create') }}" class="btn btn-primary">{{ $isStaff ? 'Tautan Google Meet Satker' : 'Jadwalkan Konsultasi' }}</a>
</div>
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<p style="color:var(--text-muted);margin-bottom:20px;">Seluruh jadwal menggunakan WIT. Verifikasi dan penyelesaian konsultasi dilakukan oleh petugas PTSP satker terkait.</p>
<div class="glass-card" style="margin-bottom:20px;">
    <div class="consultation-heading"><div class="service-icon"><i class="fas fa-list-ol"></i></div><div><h3>Antrean PTSP {{ auth()->user()->work_unit }}</h3><p class="consultation-copy">{{ $currentQueue->count() }} antrean aktif. Pelayanan berikutnya dimulai setelah antrean sebelumnya selesai.</p></div></div>
    @include('meetings.partials.queue-table', ['rows' => $currentQueue->map(function ($item) { return ['number' => $item->queueLabel(), 'date' => $item->localStart()->format('d/m/Y'), 'time' => $item->localStart()->format('H:i'), 'status' => $item->statusLabel(), 'is_you' => (int) $item->user_id === (int) auth()->id()]; })->all()])
</div>
<div class="grid-3">
@forelse($meetings as $meeting)
<div class="meeting-card">
    <div style="color:var(--primary-light);font-weight:700;letter-spacing:1px;margin-bottom:12px;">{{ $meeting->queueLabel() }} <small class="consultation-copy">· {{ $meeting->localStart()->format('d/m/Y') }}</small></div>
    <span class="badge badge-{{ $meeting->status === 'ended' ? 'success' : ($meeting->status === 'cancelled' ? 'danger' : 'info') }}">{{ $meeting->statusLabel() }}</span>
    <h3 style="margin:14px 0;">{{ $meeting->topic }}</h3>
    <p class="consultation-copy">{{ $meeting->channelLabel() }}</p>
    <p style="color:var(--text-muted);margin-bottom:8px;">{{ $meeting->work_unit }}<br>{{ $meeting->localStart()->format('d/m/Y H:i') }} WIT · {{ $meeting->duration }} menit</p>
    @if($isStaff)<p style="margin-bottom:8px;">Pemohon: {{ $meeting->user->name ?? 'Pengguna' }}</p>@endif
    <p style="margin-bottom:12px;"><strong>{{ $meeting->verificationLabel() }}</strong></p>
    @if($meeting->verification_note)<p style="margin-bottom:12px;">Catatan: {{ $meeting->verification_note }}</p>@endif
    @if($meeting->completed_at)<p style="font-size:12px;color:var(--text-muted);">Selesai: {{ $meeting->completed_at->copy()->timezone(config('ptsp.timezone'))->format('d/m/Y H:i') }} WIT</p>@endif
    @if($meeting->canJoin(auth()->user()))
                <a href="{{ route('meetings.join', $meeting->id) }}" class="btn btn-success btn-sm">Antrean / Google Meet</a>
    @elseif(in_array($meeting->status, ['scheduled', 'started']))
    <p style="font-size:12px;color:var(--text-muted);margin-bottom:12px;">{{ $meeting->isMace() ? 'Konsultasi tatap muka di PTSP. Tunggu panggilan petugas di pengadilan.' : ($meeting->verification_status === 'pending' ? 'Menunggu verifikasi petugas PTSP.' : 'Tombol bergabung tersedia pada waktu konsultasi.') }}</p>
    @endif
    @if($meeting->canManage(auth()->user()) && $meeting->status === 'scheduled' && $meeting->verification_status === 'pending')
    <form method="POST" action="{{ route('meetings.verify', $meeting->id) }}" style="margin-top:12px;">@csrf
        <input name="verification_note" class="form-control" placeholder="Catatan untuk pemohon (opsional)" maxlength="1000" style="margin-bottom:8px;">
        <button name="decision" value="verified" class="btn btn-primary btn-sm">Verifikasi</button>
        <button name="decision" value="rejected" class="btn btn-danger btn-sm">Tolak</button>
    </form>
    @endif
    @if($meeting->canManage(auth()->user()) && $meeting->canServe(auth()->user()) && optional($currentQueue->first())->id == $meeting->id)
    <div class="consultation-actions">
    @if($meeting->guestTicket && !$meeting->called_at)<form method="POST" action="{{ route('meetings.call-guest',$meeting) }}">@csrf<button class="btn btn-primary btn-sm">Panggil tamu</button></form>
    @else
    @if($meeting->status !== 'started')<form method="POST" action="{{ route('meetings.start', $meeting->id) }}">@csrf<button class="btn btn-primary btn-sm">Mulai Layani</button></form>@endif
    <form method="POST" action="{{ route('meetings.complete', $meeting->id) }}">@csrf<button class="btn btn-success btn-sm">Tandai Selesai</button></form>
    @endif
    </div>
    @endif
    @if(in_array($meeting->status, ['scheduled', 'started']) && ((int) $meeting->user_id === (int) auth()->id() || $meeting->canManage(auth()->user())))
    <form method="POST" action="{{ route('meetings.destroy', $meeting->id) }}" style="margin-top:12px;">@csrf @method('DELETE')<button class="btn btn-secondary btn-sm">Batalkan Jadwal</button></form>
    @endif
</div>
@empty
<div class="glass-card"><h3>Belum Ada Konsultasi</h3><p>Pilih jadwal yang tersedia untuk memulai konsultasi dengan PTSP satker Anda.</p></div>
@endforelse
</div>
@endsection
