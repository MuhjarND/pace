@extends('layouts.public')
@section('title','Konsultasi PTSP')
@section('content')
<div class="pace-consult-page">
@include('public.partials.page-heading', ['heading'=>'Konsultasi PTSP', 'description'=>'Tanyakan kebutuhan Anda melalui chat, Google Meet, atau tatap muka.', 'icon'=>'far fa-comments','illustration'=>'consultation'])
<div class="citizen-columns pace-form-layout">
<section class="citizen-panel citizen-consult-form" aria-labelledby="channelHeading">
@if($region)<p class="citizen-context">{{ $region->name }} @if(!$mapping) · Pemetaan pengadilan perlu konfirmasi PTSP @endif <a href="{{ route('public.finder',['region'=>$region->code]) }}">Ubah wilayah</a></p>@endif
<form method="POST" action="{{ route('public.book') }}" id="consultationForm" data-availability-endpoint="{{ route('public.availability','__UNIT__') }}" data-unit-selector="guestUnit" data-channel-selector="guestChannel" data-selected-time="{{ old('consultation_time','now') }}" novalidate>@csrf
<input type="hidden" name="request_key" value="{{ $nonce }}"><input type="hidden" name="region_code" value="{{ optional($region)->code }}">
@php($selectedChannel = old('channel', request('channel', 'chat')))
<fieldset id="guestChannel" class="pace-channel-picker"><legend id="channelHeading">Pilih cara konsultasi</legend><div class="pace-channel-grid">
@foreach(['chat'=>['Chat PTSP','Pesan dengan petugas'],'meet'=>['Google Meet','Video sesuai jadwal'],'mace'=>['Konsultasi Bersama Mace (offline)','Tatap muka di pengadilan']] as $value=>$choice)
<label class="pace-channel-choice" for="channel-{{ $value }}"><input type="radio" id="channel-{{ $value }}" name="channel" value="{{ $value }}" required {{ $selectedChannel===$value?'checked':'' }} aria-controls="guestMeetFields guestMaceInfo"><span class="pace-channel-body"><strong>{{ $choice[0] }}</strong><span>{{ $choice[1] }}</span></span></label>
@endforeach
</div></fieldset>
<section class="pace-form-section" aria-labelledby="consultIdentityHeading"><h2 id="consultIdentityHeading">Tujuan dan identitas</h2>
<div class="form-group"><label for="guestUnit" class="form-label">Pengadilan</label><select id="guestUnit" class="form-control" name="work_unit_id" required><option value="">Pilih pengadilan</option>@foreach($units as $item)<option value="{{ $item->id }}" {{ (int)old('work_unit_id',optional($unit)->id)===(int)$item->id?'selected':'' }}>{{ $item->name }}</option>@endforeach</select></div>
<div id="guestMaceInfo" class="alert alert-info" role="status" hidden>Ambil nomor antrean Mace untuk hari ini, lalu datang ke PTSP pengadilan yang dipilih. Penerimaan mengikuti jam layanan dan kuota satker. <a id="guestMaceGuide" href="#" data-endpoint="{{ route('public.consult.offline','__UNIT__') }}" hidden>Lihat panduan kunjungan</a></div>
<div id="guestIdentityFields" class="citizen-field-grid"><div class="form-group"><label for="guestName" class="form-label">Nama / panggilan</label><input id="guestName" class="form-control" name="name" required minlength="2" maxlength="100" autocomplete="name" value="{{ old('name') }}"></div><div class="form-group"><label for="guestPhone" class="form-label">Nomor WhatsApp</label><input id="guestPhone" class="form-control" name="phone" type="tel" maxlength="22" autocomplete="tel" value="{{ old('phone') }}" aria-describedby="phoneHint"><p class="field-hint" id="phoneHint">Wajib untuk Meet, opsional untuk chat dan Mace.</p></div></div>
<div id="guestMeetFields" hidden><div class="citizen-field-grid"><div class="form-group"><label for="consultationDate" class="form-label">Tanggal konsultasi</label><select id="consultationDate" name="consultation_date" class="form-control" disabled>@for($day=0;$day<=config('ptsp.consultation_booking_days');$day++)@php($date=$localNow->copy()->addDays($day))<option value="{{ $date->toDateString() }}" {{ old('consultation_date',$localNow->toDateString())===$date->toDateString()?'selected':'' }}>{{ $day===0?'Hari ini · ':'' }}{{ $date->format('d/m/Y') }}</option>@endfor</select></div><div class="form-group"><label for="consultationTime" class="form-label">Waktu konsultasi (WIT)</label><select id="consultationTime" name="consultation_time" class="form-control" disabled aria-describedby="availabilityStatus"><option value="">Pilih waktu konsultasi</option></select></div></div><div class="availability-feedback"><p class="field-hint" id="availabilityStatus" role="status">Pilih satker untuk melihat jadwal.</p><button id="availabilityRetry" type="button" class="btn btn-secondary btn-sm" hidden>Coba muat waktu</button></div></div>
</section>
<div id="guestRequestFields"><div class="form-group"><label for="guestTopic" class="form-label">Topik konsultasi</label><textarea id="guestTopic" class="form-control" name="topic" required minlength="3" maxlength="255" placeholder="Tuliskan pertanyaan atau kebutuhan Anda.">{{ old('topic',$caseContext ? 'Pertanyaan status perkara dan akta cerai '.$caseContext : (session('fee_context.case_type') ? 'Konsultasi perkiraan biaya '.(config('ptsp.case_types')[session('fee_context.case_type')]??'perkara') : '')) }}</textarea></div>
<details class="pace-location-details" {{ old('origin_region_code') || $errors->has('origin_region_code') || optional($region)->level === 4 ? 'open' : '' }}><summary id="originHeading">Wilayah domisili (opsional)</summary>@include('public.partials.consultation-location',['compactLocation'=>true])</details>
<div class="operations-check"><input type="checkbox" id="guestConsent" name="consent" value="1" required {{ old('consent')?'checked':'' }}><label for="guestConsent">Saya mengizinkan PTSP menggunakan data ini untuk melayani konsultasi dan mengirim pemberitahuan layanan ke nomor WhatsApp yang saya isi.</label></div>
</div>
<button id="bookConsultation" type="submit" class="btn btn-primary citizen-primary"><span data-submit-label>{{ $selectedChannel==='mace' ? 'Ambil nomor antrean Mace' : 'Ajukan konsultasi' }}</span> <i class="fas fa-arrow-right" aria-hidden="true"></i></button><noscript><p class="alert alert-info">Chat dan antrean Mace dapat diajukan tanpa JavaScript. Antrean Mace berlaku untuk hari ini dan dilayani secara tatap muka di pengadilan. Aktifkan JavaScript untuk melihat jadwal Google Meet.</p></noscript></form>
<details class="citizen-consult-help"><summary>Petunjuk konsultasi</summary><div>
<p>Simpan tiket untuk melihat balasan, antrean, dan status konsultasi.</p>
<p>Seluruh jadwal menggunakan WIT. Jadwal Meet mendatang menunggu verifikasi petugas; undangan dikirim ke WhatsApp saat giliran Anda siap.</p>
<p>WhatsApp hanya untuk pemberitahuan. Jangan kirim NIK, kata sandi, atau dokumen pribadi sebelum diminta petugas.</p>
</div></details>
</section>
<aside class="citizen-panel citizen-guide"><h2>Simpan tiket Anda</h2><p>Tiket membantu Anda melihat balasan, nomor antrean, dan perkembangan konsultasi.</p>
@include('public.partials.guide-steps',['steps'=>[
 ['Pilih layanan','Gunakan chat, Google Meet, atau tatap muka bersama Mace.'],
 ['Lengkapi kebutuhan','Pilih pengadilan dan ceritakan pertanyaan Anda.'],
 ['Pantau tiket','Simpan tautan tiket setelah mengajukan konsultasi.'],
]])
<p class="field-hint pace-aside-divider">Tiket konsultasi pada perangkat ini dapat dibuka kembali dari halaman ini.</p>
<div class="pace-gold-note"><h3>Jadwal dan antrean</h3><p>Jadwal menggunakan WIT. Mace melayani tatap muka di pengadilan; antrean berlaku untuk hari ini sesuai jam layanan dan kuota.</p></div>
</aside></div>
@if($tickets->count())<section class="citizen-panel citizen-result"><h2>Tiket pada perangkat ini</h2><ul class="citizen-ticket-list">@foreach($tickets as $ticket)<li><a href="{{ route('public.ticket',$ticket->public_id) }}">{{ $ticket->meeting ? $ticket->meeting->channelLabel().' · '.$ticket->meeting->queueLabel().' · '.$ticket->meeting->localStart()->format('d/m/Y H:i').' WIT' : 'Chat PTSP' }} <span>{{ $ticket->meeting ? $ticket->meeting->statusLabel() : ($ticket->room->status==='open'?'Percakapan terbuka':'Ditutup') }}</span></a></li>@endforeach</ul></section>@endif
<p class="citizen-consult-complaint"><a href="{{ route('public.complaints.create') }}">Pengaduan layanan dan gratifikasi <i class="fas fa-arrow-right" aria-hidden="true"></i></a></p>
</div>
@endsection
@section('styles')<link rel="stylesheet" href="{{ asset('css/consultation-location.css') }}?v={{ filemtime(public_path('css/consultation-location.css')) }}">@endsection
@section('scripts')<script src="{{ asset('js/availability.js') }}?v={{ filemtime(public_path('js/availability.js')) }}" defer></script>@endsection
