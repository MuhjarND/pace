@extends('layouts.app')
@section('title', 'Antrean Konsultasi')
@section('page-title', 'Antrean Konsultasi Google Meet')
@section('styles')@include('meetings.partials.styles')@endsection
@section('content')
<div class="section-header"><h2><i class="fas fa-users" style="color:var(--primary-light);margin-right:10px;"></i> Antrean Konsultasi</h2><a href="{{ route('meetings.index') }}" class="btn btn-secondary">Daftar Jadwal</a></div>
<div class="consultation-layout">
    <div class="glass-card">
        <div class="consultation-heading"><div class="service-icon"><i class="fas fa-video"></i></div><div><h3>PTSP {{ $meeting->work_unit }}</h3><p class="consultation-copy">{{ $meeting->topic }}</p></div></div>
        <div class="queue-summary"><div class="queue-ticket"><small>NOMOR ANDA</small><strong>{{ $meeting->queueLabel() }}</strong></div><div><h3 id="queueTitle">Menunggu giliran</h3><p id="queueDescription" class="consultation-copy">Ada {{ $queueState['ahead'] }} antrean sebelum Anda. Mohon menunggu hingga antrean sebelumnya selesai untuk dilayani.</p></div></div>
        <div class="consultation-note"><i class="fas fa-circle-info" style="color:var(--primary-light);"></i> Anda dapat membuka Google Meet. Jika masih ada antrean, tunggu di ruang rapat sampai petugas memanggil nomor Anda.</div>
        <form method="POST" action="{{ route('meetings.enter', $meeting->id) }}" class="consultation-actions">@csrf<button id="enterMeet" class="btn btn-primary"><i class="fas fa-video"></i> Buka Google Meet</button><a href="{{ route('meetings.index') }}" class="btn btn-secondary">Kembali</a></form>
        <p id="queueConnection" class="consultation-copy" role="status" style="font-size:12px;margin-top:16px;">Urutan diperbarui otomatis selama halaman ini terbuka.</p>
    </div>
    <div class="glass-card"><div class="consultation-heading"><div class="service-icon"><i class="fas fa-list-ol"></i></div><div><h3>Antrean PTSP Saat Ini</h3><p class="consultation-copy">Urutan pelayanan satker Anda</p></div></div>@include('meetings.partials.queue-table', ['rows' => $queueState['entries']])</div>
</div>
@endsection
@section('scripts')
<script>
const queueStatusUrl = @json(route('meetings.queue', $meeting->id));
async function refreshQueue() {
    try {
        const response = await fetch(queueStatusUrl, {headers:{'Accept':'application/json'}});
        if (!response.ok) throw new Error('Queue unavailable');
        const state = await response.json();
        document.getElementById('queueTitle').textContent = !state.position ? state.status : (state.ready ? 'Giliran Anda sudah tiba' : 'Menunggu giliran');
        document.getElementById('queueDescription').textContent = !state.position ? 'Antrean ini tidak lagi aktif. Periksa daftar jadwal konsultasi Anda.' : (state.ready ? 'Silakan bergabung ke Google Meet untuk dilayani petugas PTSP.' : `Ada ${state.ahead} antrean sebelum Anda. Mohon menunggu hingga antrean sebelumnya selesai untuk dilayani.`);
        document.getElementById('enterMeet').disabled = !state.position;
        const body = document.getElementById('queueRows');
        body.replaceChildren();
        state.entries.forEach(entry => {
            const tr = document.createElement('tr');
            if (entry.is_you) tr.className = 'is-you';
            [entry.number + (entry.is_you ? ' · Anda' : ''), entry.date + ' · ' + entry.time, entry.status].forEach(text => { const td = document.createElement('td'); td.textContent = text; tr.appendChild(td); });
            body.appendChild(tr);
        });
        document.getElementById('queueConnection').textContent = 'Antrean sudah diperbarui.';
    } catch (error) { document.getElementById('queueConnection').textContent = 'Antrean belum dapat diperbarui. Mencoba lagi...'; }
    finally { setTimeout(refreshQueue, 5000); }
}
setTimeout(refreshQueue, 5000);
</script>
@endsection
