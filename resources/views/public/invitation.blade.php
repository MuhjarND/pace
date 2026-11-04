@extends('layouts.public')
@section('title','Undangan konsultasi')
@section('content')
<div class="citizen-intro citizen-intro-compact"><h1>Undangan Google Meet</h1><p>{{ $meeting->work_unit }}</p></div><section class="citizen-panel citizen-invitation"><strong class="citizen-queue-number">{{ $meeting->queueLabel() }}</strong><h2>{{ $meeting->called_at && $queue['ready'] ? 'Petugas siap melayani Anda' : 'Menunggu giliran konsultasi' }}</h2><p>{{ $meeting->localStart()->format('d/m/Y H:i') }} WIT · {{ $meeting->statusLabel() }}</p><p class="citizen-description">Undangan ini hanya berlaku untuk konsultasi terkait. Jangan bagikan tautan kepada orang lain.</p><form method="POST" action="{{ route('public.invitation.join',$token) }}">@csrf<button type="submit" class="btn btn-primary" {{ !$meeting->called_at || !$queue['ready']?'disabled':'' }}>Gabung Google Meet</button></form><p class="field-hint">Jika belum siap, muat ulang halaman untuk memeriksa status terbaru.</p></section>
@endsection
