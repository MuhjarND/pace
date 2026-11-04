@extends('layouts.app')
@section('title', 'Tautan Google Meet PTSP')
@section('page-title', 'Tautan Google Meet Satker')
@section('styles')@include('meetings.partials.styles')@endsection
@section('content')
<div class="operations-manage-links"><a class="btn btn-secondary" href="{{ route('operations.queue') }}">Antrean pelayanan</a><a class="btn btn-secondary" href="{{ route('operations.hours') }}">Jam layanan satker</a></div>
<div class="section-header page-heading"><div><p class="eyebrow">Konsultasi daring</p><h1>Tautan Google Meet satker</h1></div><a href="{{ route('meetings.index') }}" class="btn btn-secondary">Daftar Jadwal dan Antrean</a></div>
<div class="consultation-layout">
    <div class="glass-card">
        <div class="consultation-heading"><div class="service-icon"><i class="fas fa-video"></i></div><div><h3>{{ $unit }}</h3><p class="consultation-copy">Satu tautan untuk seluruh konsultasi satker.</p></div></div>
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('meetings.settings') }}">@csrf
            <div class="form-group"><label for="joinUrl" class="form-label">Tautan Tetap Google Meet</label><input type="url" id="joinUrl" name="join_url" class="form-control" value="{{ old('join_url', optional($setting)->join_url) }}" placeholder="https://meet.google.com/abc-defg-hij" maxlength="255" required></div>
            <p class="consultation-copy">Gunakan tautan ruang yang sudah Anda siapkan untuk digunakan sepanjang tahun. Aplikasi memakai ulang tautan ini untuk semua jadwal dan tidak menetapkan tanggal kedaluwarsa.</p>
            <div class="consultation-actions"><button class="btn btn-primary"><i class="fas fa-save"></i> Simpan Tautan</button>@if($setting)<a href="{{ $setting->join_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-secondary">Buka Google Meet</a>@endif</div>
        </form>
    </div>
    <div class="glass-card"><div class="consultation-heading"><div class="service-icon"><i class="fas fa-users"></i></div><div><h3>Pelayanan Konsultasi</h3><p class="consultation-copy">Pengguna memilih jadwal secara mandiri.</p></div></div>
        <p class="consultation-copy">Setelah tautan disimpan, pengguna dapat memilih tanggal, waktu, dan topik. Seluruh permohonan tampil pada daftar konsultasi satker Anda.</p>
        <div class="consultation-note consultation-copy">Verifikasi jadwal mendatang, layani sesuai urutan antrean, lalu tandai <strong>Selesai</strong> agar pengguna berikutnya mendapat giliran.</div>
        <a href="{{ route('meetings.index') }}" class="btn btn-primary">Lihat Jadwal dan Antrean</a>
    </div>
</div>
@endsection
