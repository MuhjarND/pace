@extends('layouts.app')
@section('title', 'Antrean pelayanan')
@section('page-title', 'Operasional PTSP')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Operasional PTSP</p><h1>Antrean pelayanan</h1><p class="page-description">Pantau giliran dan verifikasi konsultasi satker Anda.</p></div><div class="operations-refresh"><button type="button" class="btn btn-primary" id="queueRefresh"><i class="fas fa-rotate-right" aria-hidden="true"></i> Muat ulang</button><p id="queueUpdateStatus" role="status">Memuat pembaruan otomatis…</p></div></div>
@include('operations.partials.errors')
<div class="operations-unit-band"><div class="operations-aside-icon"><i class="fas fa-building-columns" aria-hidden="true"></i></div><div><span>Satuan kerja yang dipantau</span><strong>{{ $unit->name }}</strong>@if(!$canManage)<p class="field-hint">Monitoring baca saja · tindakan tetap dilakukan petugas satker ini.</p>@endif</div>
@if(auth()->user()->canMonitor())<form action="{{ route('operations.queue') }}" method="GET" class="operations-unit-filter"><label for="queueUnit">Unit yang dipantau</label><select name="work_unit" id="queueUnit" class="form-control">@foreach($workUnits as $name)<option value="{{ $name }}" {{ $name === $unit->name ? 'selected' : '' }}>{{ $name }}</option>@endforeach</select><button type="submit" class="btn btn-secondary">Tampilkan</button></form>@endif
</div>
<div id="queueBoard" data-endpoint="{{ route('operations.queue.data', request()->only('work_unit', 'tab', 'date', 'page')) }}">@include('operations.partials.queue-board')</div>
<noscript><p class="alert alert-info">Pembaruan otomatis memerlukan JavaScript. Muat ulang halaman untuk mendapatkan antrean terbaru.</p></noscript>
@endsection
@section('scripts')<script src="{{ asset('js/operations.js') }}?v={{ filemtime(public_path('js/operations.js')) }}" defer></script>@endsection
