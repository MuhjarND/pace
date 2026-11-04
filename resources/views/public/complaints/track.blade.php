@extends('layouts.public')
@section('title','Lacak pengaduan')
@section('content')
@include('public.partials.page-heading', ['heading'=>'Pantau tindak lanjut laporan.', 'description'=>'Masukkan nomor laporan dan kode rahasia yang Anda simpan saat mengirim pengaduan.', 'icon'=>'fas fa-shield-halved','illustration'=>'privacy'])
<div class="citizen-columns"><section class="citizen-panel"><h2>Lacak pengaduan</h2><p class="citizen-description">Nomor laporan saja tidak dapat digunakan untuk membuka isi laporan.</p>
<form action="{{ route('public.complaints.unlock') }}" method="POST" data-busy-message="Memeriksa akses laporan…" novalidate>@csrf
<div class="form-group"><label for="trackReference" class="form-label">Nomor laporan</label><input id="trackReference" name="reference" class="form-control" required maxlength="40" autocomplete="off" value="{{ old('reference') }}" placeholder="ADU-20261102-XXXXXXXX"></div>
<div class="form-group"><label for="trackCode" class="form-label">Kode rahasia</label><input type="password" id="trackCode" data-secret-label="kode rahasia" name="access_code" class="form-control" required maxlength="64" autocomplete="off" spellcheck="false" aria-describedby="codeHint"><p id="codeHint" class="field-hint">Kode membedakan huruf besar dan kecil. Jangan membagikannya kepada orang lain.</p></div>
<button class="btn btn-primary citizen-primary" type="submit">Buka laporan saya <i class="fas fa-lock-open" aria-hidden="true"></i></button><p class="field-hint citizen-submit-status" data-form-status role="status"></p>
</form></section><aside class="citizen-panel citizen-guide"><h2>Akses pribadi pelapor</h2><p>Kode rahasia memberi akses ke satu laporan beserta tanggapannya. Pengelola pengaduan PTA tidak memerlukan kode ini untuk menindaklanjuti.</p><p>Kode tidak dapat dikirim ulang atau dipulihkan karena laporan tidak menyimpan identitas maupun kontak pelapor. Simpan kode di tempat aman; tanpa kode dan sesi yang masih berlaku, Anda tidak dapat membuka kembali tiket.</p><a class="btn btn-secondary" href="{{ route('public.complaints.create') }}">Buat pengaduan baru</a></aside></div>
@endsection
