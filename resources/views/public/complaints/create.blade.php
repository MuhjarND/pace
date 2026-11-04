@extends('layouts.public')
@section('title','Pengaduan layanan dan gratifikasi')
@section('content')
@include('public.partials.page-heading', ['heading'=>'Sampaikan pengaduan Anda.', 'description'=>'Pengaduan layanan dan laporan gratifikasi di setiap satker diterima oleh pengelola pengaduan PTA Papua Barat.', 'icon'=>'fas fa-shield-halved','illustration'=>'privacy'])
<div class="citizen-columns">
<section class="citizen-panel"><div class="complaint-form-heading"><div><p class="eyebrow">Ditujukan ke PTA Papua Barat</p><h2>Buat laporan</h2></div><i class="fas fa-shield-halved" aria-hidden="true"></i></div>
<p class="citizen-description">Laporan anonim, tanpa nama atau kontak pelapor. Isi dan bukti hanya dapat dibuka pengelola pengaduan PTA yang berwenang.</p>
<form method="POST" action="{{ route('public.complaints.store') }}" enctype="multipart/form-data" data-busy-message="Menyimpan laporan dan bukti…" novalidate>@csrf
<input type="hidden" name="request_key" value="{{ $nonce }}">
<div class="form-group"><label for="complaintUnit" class="form-label">Satuan kerja terkait</label><select id="complaintUnit" name="work_unit_id" class="form-control" required><option value="">Pilih satuan kerja</option>@foreach($units as $item)<option value="{{ $item->id }}" {{ (int)old('work_unit_id',optional($unit)->id)===(int)$item->id?'selected':'' }}>{{ $item->name }}</option>@endforeach</select></div>
<div class="form-group"><label for="complaintType" class="form-label">Jenis laporan</label><select id="complaintType" name="type" class="form-control" required><option value="">Pilih jenis laporan</option>@foreach(\App\Complaint::TYPES as $value=>$label)<option value="{{ $value }}" {{ old('type')===$value?'selected':'' }}>{{ $label }}</option>@endforeach</select></div>
<div class="form-group"><label for="complaintSubject" class="form-label">Judul laporan</label><input id="complaintSubject" name="subject" class="form-control" required minlength="5" maxlength="150" value="{{ old('subject') }}" placeholder="Ringkasan singkat kejadian"></div>
<div class="citizen-field-grid"><div class="form-group"><label for="complaintDate" class="form-label">Tanggal kejadian</label><input type="date" id="complaintDate" name="occurred_on" class="form-control" required max="{{ now()->timezone(config('ptsp.timezone'))->toDateString() }}" value="{{ old('occurred_on') }}"></div><div class="form-group"><label for="complaintLocation" class="form-label">Tempat kejadian <span class="field-optional">(opsional)</span></label><input id="complaintLocation" name="location" class="form-control" maxlength="200" value="{{ old('location') }}" placeholder="Loket, ruang, atau lokasi terkait"></div></div>
<div class="form-group"><label for="complaintPerson" class="form-label">Pihak terkait <span class="field-optional">(opsional)</span></label><input id="complaintPerson" name="reported_person" class="form-control" maxlength="150" value="{{ old('reported_person') }}" placeholder="Nama atau jabatan, jika diketahui"></div>
<div class="form-group"><label for="complaintDescription" class="form-label">Kronologi kejadian</label><textarea id="complaintDescription" name="description" class="form-control complaint-chronology" style="resize:none" required minlength="30" maxlength="10000" rows="7" aria-describedby="chronologyHint">{{ old('description') }}</textarea><p id="chronologyHint" class="field-hint">Ceritakan apa yang terjadi, kapan, di mana, dan pihak yang terkait. Untuk gratifikasi, jelaskan bentuk pemberian atau permintaan jika diketahui. Minimal 30 karakter.</p></div>
<div class="form-group"><label for="complaintEvidence" class="form-label">Bukti pendukung <span class="field-optional">(opsional)</span></label><input type="file" id="complaintEvidence" name="evidence[]" class="form-control complaint-file-input" accept="application/pdf,image/jpeg,image/png" multiple data-complaint-files aria-describedby="evidenceHint evidenceStatus"><p id="evidenceHint" class="field-hint">PDF, JPG, atau PNG. Maksimal 3 berkas, masing-masing 5 MB. Jika pengiriman gagal, pilih ulang lampiran sebelum mencoba lagi.</p><ul class="complaint-file-list" data-file-list></ul><p id="evidenceStatus" class="field-hint" role="status" data-file-status></p></div>
<div class="complaint-identity"><h3>Identitas pelapor dirahasiakan</h3><p class="field-hint">Formulir tidak meminta nama, email, atau nomor kontak. Hindari mencantumkan identitas Anda pada judul, kronologi, atau berkas bukti jika ingin tetap anonim. Simpan kode rahasia untuk membaca tanggapan pengelola.</p></div>
<div class="operations-check"><input type="checkbox" id="complaintConsent" name="consent" value="1" required {{ old('consent')?'checked':'' }}><label for="complaintConsent">Saya menyampaikan informasi sesuai yang saya ketahui dan mengizinkan pengelola pengaduan PTA menindaklanjuti laporan ini.</label></div>
<button type="submit" class="btn btn-primary citizen-primary">Kirim pengaduan ke PTA <i class="fas fa-arrow-right" aria-hidden="true"></i></button><p class="field-hint citizen-submit-status" role="status" data-form-status></p>
</form></section>
<aside class="citizen-panel citizen-guide"><h2>Laporan ditangani di PTA</h2>
@include('public.partials.guide-steps', ['steps'=>[
    ['Pilih satker dan jelaskan kejadian','Lengkapi kronologi dan bukti jika ada. Nama serta kontak pelapor tidak diminta.'],
    ['Simpan nomor dan kode rahasia','Kode diberikan setelah pengiriman. Simpan sendiri untuk membuka laporan pada perangkat lain.'],
    ['Pantau tanggapan pengelola','Status dan pesan dari pengelola PTA dapat dilihat melalui tiket pengaduan.'],
]])
<div class="complaint-guide-action"><a class="btn btn-secondary" href="{{ route('public.complaints.track') }}">Lacak pengaduan saya</a></div></aside>
</div>
@endsection
@section('scripts')<script src="{{ asset('js/complaints.js') }}?v={{ filemtime(public_path('js/complaints.js')) }}" defer></script>@endsection
