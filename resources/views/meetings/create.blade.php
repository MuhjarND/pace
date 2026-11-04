@extends('layouts.app')
@section('title', 'Jadwalkan Konsultasi')
@section('page-title', 'Konsultasi Google Meet')
@section('styles')@include('meetings.partials.styles')@endsection
@section('content')
<div class="section-header page-heading"><div><p class="eyebrow">Konsultasi daring</p><h1>Jadwalkan konsultasi</h1></div><a href="{{ route('meetings.index') }}" class="btn btn-secondary">Daftar Jadwal Saya</a></div>
<div class="consultation-layout">
    <div class="glass-card">
        <div class="consultation-heading"><div class="service-icon"><i class="fas fa-video"></i></div><div><h3>PTSP {{ auth()->user()->work_unit }}</h3><p class="consultation-copy">Pilih waktu konsultasi dan sampaikan topik Anda.</p></div></div>
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        @if(!$setting)
        <div class="empty-state"><i class="fas fa-link"></i><h3>Tautan PTSP Belum Tersedia</h3><p>Hubungi petugas agar tautan Google Meet satker disiapkan.</p><a href="{{ route('chat.index') }}" class="btn btn-primary">Chat PTSP</a></div>
        @else
        <form method="POST" action="{{ route('meetings.store') }}" id="consultationForm" data-availability-endpoint="{{ route('meetings.availability') }}" data-selected-time="{{ old('consultation_time', 'now') }}">@csrf
            <div class="grid-2">
                <div class="form-group"><label for="consultationDate" class="form-label">Tanggal Konsultasi</label><select name="consultation_date" id="consultationDate" class="form-control" required>
                    @for($day = 0; $day <= config('ptsp.consultation_booking_days'); $day++)
                    @php($date = $localNow->copy()->addDays($day))
                    <option value="{{ $date->format('Y-m-d') }}" {{ old('consultation_date', $localNow->format('Y-m-d')) === $date->format('Y-m-d') ? 'selected' : '' }}>{{ $day === 0 ? 'Hari ini · ' : '' }}{{ $date->format('d/m/Y') }}</option>
                    @endfor
                </select></div>
                <div class="form-group"><label for="consultationTime" class="form-label">Waktu Konsultasi (WIT)</label><select id="consultationTime" name="consultation_time" class="form-control" required aria-describedby="availabilityStatus">
                    <option value="">Pilih waktu konsultasi</option>
                    @if($availability['can_now'])<option value="now" {{ old('consultation_time', 'now') === 'now' ? 'selected' : '' }}>Sekarang · Masuk antrean</option>@endif
                    @foreach($availability['slots'] as $time)<option value="{{ $time }}" {{ old('consultation_time') === $time ? 'selected' : '' }}>{{ $time }} WIT</option>@endforeach
                </select></div>
            </div>
            <div class="availability-feedback"><p id="availabilityStatus" class="field-hint" role="status">{{ $availability['message'] }}@if($availability['quota_remaining'] !== null) Sisa {{ $availability['quota_remaining'] }} kuota.@endif</p><button type="button" id="availabilityRetry" class="btn btn-secondary btn-sm" hidden>Coba muat waktu</button></div>
            <noscript><p class="alert alert-info">Aktifkan JavaScript untuk memperbarui pilihan waktu saat mengganti tanggal, atau hubungi petugas melalui Chat PTSP.</p></noscript>
            <div class="form-group"><label for="topic" class="form-label">Topik Konsultasi</label><textarea id="topic" name="topic" class="form-control" maxlength="255" placeholder="Tuliskan hal yang ingin dikonsultasikan dengan petugas PTSP..." required>{{ old('topic') }}</textarea></div>
            <div class="consultation-note consultation-copy"><i class="fas fa-circle-info" style="color:var(--primary-light);"></i> Pilih <strong>Sekarang</strong> untuk masuk antrean hari ini. Jika tidak ada antrean, Anda langsung diarahkan ke Google Meet. Jadwal mendatang akan tercatat untuk diverifikasi petugas.</div>
            <div class="consultation-actions"><button type="submit" class="btn btn-primary" id="bookConsultation" {{ !$availability['can_now'] && !count($availability['slots']) ? 'disabled' : '' }}><i class="fas fa-calendar-check"></i> Ajukan Konsultasi</button><a href="{{ route('chat.index') }}" class="btn btn-secondary">Chat PTSP</a></div>
        </form>
        @endif
    </div>
    <div class="glass-card"><div class="consultation-heading"><div class="service-icon"><i class="fas fa-list-ol"></i></div><div><h3>Antrean Satker Saat Ini</h3><p class="consultation-copy">{{ $queue->count() }} pengguna sedang dilayani atau menunggu</p></div></div>
        @include('meetings.partials.queue-table', ['rows' => $queue->map(function ($meeting) { return ['number' => $meeting->queueLabel(), 'date' => $meeting->localStart()->format('d/m/Y'), 'time' => $meeting->localStart()->format('H:i'), 'status' => $meeting->statusLabel()]; })->all()])
        <p class="consultation-copy" style="font-size:12px;margin-top:16px;">Nomor antrean terpisah untuk setiap satuan kerja. Nama dan topik pengguna lain tidak ditampilkan.</p>
    </div>
</div>
@endsection
@section('scripts')<script src="{{ asset('js/availability.js') }}?v={{ filemtime(public_path('js/availability.js')) }}" defer></script>@endsection
