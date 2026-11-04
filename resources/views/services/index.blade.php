@extends('layouts.app')
@section('title', 'Layanan PTSP')
@section('page-title', 'Layanan')

@section('content')
@include('partials.page-heading', ['eyebrow' => 'Informasi dan bantuan', 'heading' => 'Temukan layanan Anda', 'description' => 'Pilih kebutuhan Anda. Petugas PTSP satker akan membantu melalui percakapan.'])
<div class="catalog-toolbar">
    <div class="catalog-search">
        <i class="fas fa-search" aria-hidden="true"></i>
        <input type="search" id="serviceSearch" aria-label="Cari layanan PTSP" placeholder="Cari layanan, misalnya akta cerai..." autocomplete="off">
        <button type="button" class="voice-search-btn" data-voice-target="serviceSearch" title="Cari dengan suara" aria-label="Cari layanan dengan suara"><i class="fas fa-microphone" aria-hidden="true"></i></button>
    </div>
    @if(auth()->user()->canManageMasterData())
    <button type="button" class="btn btn-primary" data-dialog-open="addServiceModal"><i class="fas fa-plus" aria-hidden="true"></i> Tambah layanan</button>
    @endif
</div>
<div class="category-filters" role="group" aria-label="Filter kategori layanan">
    @foreach(['' => 'Semua layanan', 'perkara' => 'Perkara', 'persidangan' => 'Persidangan', 'kepaniteraan' => 'Kepaniteraan', 'informasi' => 'Informasi', 'pengaduan' => 'Pengaduan'] as $key => $label)
    <button type="button" class="filter-pill" data-category-filter="{{ $key }}" aria-pressed="{{ $key === '' ? 'true' : 'false' }}">{{ $label }}</button>
    @endforeach
</div>
<div class="catalog-caption"><p id="serviceResultCount" role="status" aria-live="polite">{{ $services->count() }} layanan tersedia</p><span><i class="fas fa-landmark" aria-hidden="true"></i> PTSP satker Anda</span></div>
<div class="grid-4 catalog-grid" id="servicesGrid">
    @foreach($services as $service)
    <article class="service-card" data-name="{{ mb_strtolower($service->name . ' ' . $service->description) }}" data-category="{{ $service->category }}">
        <div class="service-card-top"><div class="service-icon" aria-hidden="true"><i class="fas {{ $service->icon }}"></i></div><span class="badge badge-primary">{{ ucfirst($service->category) }}</span></div>
        <h2>{{ $service->name }}</h2>
        <p>{{ $service->description }}</p>
        @if($service->isComplaintChannel())
        <a class="btn btn-secondary service-action" href="{{ route('public.complaints.create') }}">Kirim pengaduan ke PTA <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        @else
        <form novalidate method="POST" action="{{ route('chat.createRoom') }}" class="service-action">
            @csrf
            <input type="hidden" name="service_id" value="{{ $service->id }}">
            <button type="submit" class="btn btn-secondary" aria-label="Konsultasi {{ $service->name }}">Mulai konsultasi <i class="fas fa-arrow-right" aria-hidden="true"></i></button>
        </form>
        @endif
    </article>
    @endforeach
</div>
<div id="serviceNoResults" class="empty-state glass-card" hidden>
    <i class="fas fa-magnifying-glass" aria-hidden="true"></i><h2>Layanan tidak ditemukan</h2><p>Coba kata kunci lain atau tampilkan semua kategori.</p><button type="button" class="btn btn-secondary" data-reset-catalog>Tampilkan semua layanan</button>
</div>

@if(auth()->user()->canManageMasterData())
<!-- Add Service Modal -->
<div class="modal-backdrop" id="addServiceModal" role="dialog" aria-modal="true" aria-labelledby="addServiceTitle" data-reopen="{{ $errors->any() ? 'true' : 'false' }}">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="addServiceTitle">Tambah layanan</h2>
            <button class="modal-close" type="button" data-dialog-close aria-label="Tutup formulir layanan"><i class="fas fa-times"></i></button>
        </div>
        <form novalidate method="POST" action="{{ route('services.store') }}">
            @csrf
            @if($errors->any())<div class="alert alert-danger" role="alert"><div>Periksa kembali isian layanan.<ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
            <div class="form-group">
                <label class="form-label">Nama Layanan</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" maxlength="255" required>
            </div>
            <div class="form-group">
                <label class="form-label">Deskripsi</label>
                <textarea name="description" class="form-control" style="resize:none" rows="4" required>{{ old('description') }}</textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Icon (Font Awesome)</label>
                <input type="text" name="icon" class="form-control" placeholder="fa-building" value="{{ old('icon', 'fa-concierge-bell') }}">
            </div>
            <div class="form-group">
                <label class="form-label">Kategori</label>
                <select name="category" class="form-control">
                    <option value="perkara" {{ old('category') === 'perkara' ? 'selected' : '' }}>Perkara</option>
                    <option value="persidangan" {{ old('category') === 'persidangan' ? 'selected' : '' }}>Persidangan</option>
                    <option value="kepaniteraan" {{ old('category') === 'kepaniteraan' ? 'selected' : '' }}>Kepaniteraan</option>
                    <option value="informasi" {{ old('category') === 'informasi' ? 'selected' : '' }}>Informasi</option>
                    <option value="pengaduan" {{ old('category') === 'pengaduan' ? 'selected' : '' }}>Pengaduan</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                <i class="fas fa-save"></i> Simpan layanan
            </button>
        </form>
    </div>
</div>
@endif
@endsection

@section('scripts')
<script src="{{ asset('js/catalog.js') }}?v={{ filemtime(public_path('js/catalog.js')) }}"></script>
@endsection
