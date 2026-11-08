@extends('layouts.public')
@section('title','Konsultasi Bersama Mace')
@section('content')
<div class="citizen-page-heading"><div><h1>Konsultasi Bersama Mace</h1><p>Konsultasi offline · Tatap muka di pengadilan</p></div><a class="btn btn-secondary" href="{{ route('public.consult',['unit'=>$unit->id]) }}">Ubah cara konsultasi</a></div>
<div class="citizen-columns">
<section class="citizen-panel">
    <h2>{{ $unit->name }}</h2>
    <p>Silakan datang ke PTSP pengadilan ini untuk konsultasi bersama Mace. Anda dapat mengambil nomor antrean hari ini melalui formulir konsultasi, sesuai jam layanan dan kuota satker.</p>
    <dl class="citizen-details">
        <div><dt>Alamat</dt><dd>{{ $unit->address ?: 'Alamat belum dipublikasikan. Hubungi PTSP sebelum berkunjung.' }}</dd></div>
        <div><dt>Telepon</dt><dd>{{ $unit->phone ?: 'Nomor telepon belum dipublikasikan.' }}</dd></div>
        <div><dt>Jam layanan (WIT)</dt><dd>
            @if($hours->count())
                @foreach($hours as $hour)<span class="citizen-hours-line">{{ ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][$hour->weekday] }} {{ $hour->opens_at }}–{{ $hour->closes_at }}@if($hour->break_start && $hour->break_end) · Istirahat {{ $hour->break_start }}–{{ $hour->break_end }}@endif</span>@endforeach
            @else Jam layanan belum tersedia. Konfirmasikan waktu kunjungan kepada PTSP. @endif
        </dd></div>
    </dl>
    @if($holidays->count())<h3>Tanggal tutup layanan</h3><ul>@foreach($holidays as $holiday)<li>{{ \Carbon\Carbon::parse($holiday->date)->format('d/m/Y') }} · {{ $holiday->name }}</li>@endforeach</ul>@endif
    <div class="citizen-actions">
        <a class="btn btn-primary" href="{{ route('public.consult',['unit'=>$unit->id,'channel'=>'mace','new'=>1]) }}">Ambil nomor antrean Mace</a>
        @if($unit->address)<a class="btn btn-primary" href="https://www.google.com/maps/search/?api=1&amp;query={{ rawurlencode($unit->name.' '.$unit->address) }}" target="_blank" rel="noopener noreferrer">Petunjuk arah <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></a>@endif
        @if($unit->website)<a class="btn btn-secondary" href="{{ $unit->website }}" target="_blank" rel="noopener noreferrer">Situs resmi pengadilan</a>@endif
    </div>
</section>
<aside class="citizen-panel citizen-guide"><h2>Sebelum berkunjung</h2><p>Konfirmasikan ketersediaan layanan bersama Mace dan waktu kunjungan kepada PTSP pengadilan yang dipilih.</p>@if($unit->public_requirements)<h3>Persiapan konsultasi</h3><p class="citizen-prewrap">{{ $unit->public_requirements }}</p>@endif<p>Setibanya di pengadilan, sampaikan kepada petugas PTSP bahwa Anda ingin konsultasi bersama Mace.</p></aside>
</div>
@endsection
