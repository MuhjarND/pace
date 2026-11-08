@extends('layouts.public')
@section('title','Cari pengadilan')
@section('content')
<div class="citizen-showcase">
<div class="citizen-showcase-copy"><h1>Layanan pengadilan,<br><span>dari mana saja.<svg class="citizen-heading-rays" aria-hidden="true" focusable="false" viewBox="0 0 48 60"><path d="M8 22 23 5M15 34 40 24M14 47 30 54" fill="none" stroke="currentColor" stroke-width="9" stroke-linecap="round"/></svg></span></h1><p>Cari pengadilan, cek perkara, dan konsultasi tanpa akun.</p>
</div>
<figure class="citizen-showcase-art" aria-hidden="true">@include('public.partials.illustration',['scene'=>'hero','priority'=>true])</figure>
<section class="citizen-panel citizen-search-panel" id="pilihWilayah" tabindex="-1" aria-labelledby="finderHeading"><h2 id="finderHeading">Temukan pengadilan Anda</h2><form action="{{ route('public.finder') }}#hasil" method="GET" novalidate>
@include('public.partials.region-picker',['selectedRegion'=>optional($region)->code,'fieldName'=>'region','pickerId'=>'finder','requireProvince'=>true])
<div class="citizen-search-actions"><button class="btn btn-primary citizen-primary" type="submit">Cari pengadilan</button></div></form></section>
</div>
@if($region)
<section class="citizen-panel citizen-result" id="hasil"><p class="field-hint">Hasil pencarian · {{ $region->name }}</p>
@if($mapping)<h2>{{ $mapping->unit->name }}</h2><p class="citizen-description">Pengadilan yang melayani wilayah ini.</p><dl class="citizen-details"><div><dt>Alamat</dt><dd>{{ $mapping->unit->address ?: 'Alamat belum dilengkapi oleh satker.' }}</dd></div><div><dt>Telepon</dt><dd>{{ $mapping->unit->phone ?: 'Hubungi petugas melalui konsultasi PTSP.' }}</dd></div><div><dt>Jam layanan</dt><dd>@if($mapping->unit->schedule_enabled)@foreach($mapping->unit->hours()->where('is_open',true)->orderBy('weekday')->get() as $hour)<span class="citizen-hours-line">{{ ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][$hour->weekday] }} {{ $hour->opens_at }}–{{ $hour->closes_at }} WIT</span>@endforeach @else Jam layanan belum dipublikasikan satker. @endif</dd></div></dl>
@if($mapping->unit->public_requirements)<h3>Persiapan konsultasi</h3><p class="citizen-prewrap">{{ $mapping->unit->public_requirements }}</p>@endif
<p class="field-hint">Rujukan wilayah: <a href="{{ $mapping->source_url }}" target="_blank" rel="noopener noreferrer">{{ $mapping->source_title }}</a>, berlaku {{ $mapping->effective_date->format('d/m/Y') }}.</p><p class="field-hint">Tempat mengajukan perkara juga dapat bergantung pada jenis perkara dan domisili para pihak. Konfirmasikan kebutuhan Anda kepada PTSP.</p><div class="citizen-actions"><a class="btn btn-primary" href="{{ route('public.consult',['region'=>$region->code]) }}">Konsultasi PTSP setempat</a><a class="btn btn-secondary" href="{{ route('public.fees',['region'=>$region->code]) }}">Cek perkiraan biaya</a>@if($mapping->unit->address)<a class="btn btn-secondary" href="https://www.google.com/maps/search/?api=1&amp;query={{ rawurlencode($mapping->unit->name.' '.$mapping->unit->address) }}" target="_blank" rel="noopener noreferrer">Petunjuk arah</a>@endif @if($mapping->unit->website)<a class="btn btn-secondary" href="{{ $mapping->unit->website }}" target="_blank" rel="noopener noreferrer">Situs resmi</a>@endif</div>
@else<h2>Perlu konfirmasi PTSP</h2><p>Pemetaan pengadilan untuk wilayah ini belum terverifikasi. Petugas dapat membantu memastikan satker yang sesuai.</p><a class="btn btn-primary" href="{{ route('public.consult',['region'=>$region->code]) }}">Konsultasi dengan PTSP</a>@endif</section>
@endif
<section class="citizen-service-gallery" aria-label="Layanan untuk Anda"><nav class="citizen-art-links" aria-label="Pilih layanan">
@include('public.partials.service-links',['withIllustrations'=>true,'withDescriptions'=>false,'excludeFinder'=>true,'preserveContext'=>true])
</nav></section>
@include('public.partials.statistics')
@endsection
@section('styles')<link rel="stylesheet" href="{{ asset('css/public-statistics.css') }}?v={{ filemtime(public_path('css/public-statistics.css')) }}">@endsection
