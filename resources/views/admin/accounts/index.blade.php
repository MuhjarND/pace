@extends('layouts.app')
@section('title', 'Akun pengguna')
@section('page-title', 'Administrasi')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Administrasi pusat</p><h1>Akun pengguna</h1><p class="page-description">Kelola peran, penempatan satker, dan akses pengguna.</p></div><a href="{{ route('admin.accounts.create') }}" class="btn btn-primary"><i class="fas fa-plus" aria-hidden="true"></i> Tambah akun</a></div>
@include('operations.partials.errors')
<div class="glass-card operations-card">
    <form novalidate method="GET" class="operations-filters" action="{{ route('admin.accounts.index') }}">
        <div class="form-group filter-grow"><label for="accountSearch" class="form-label">Cari akun</label><input id="accountSearch" name="q" value="{{ request('q') }}" type="search" maxlength="80" class="form-control" placeholder="Nama, email, atau satker"></div>
        <div class="form-group"><label for="roleFilter" class="form-label">Peran</label><select id="roleFilter" name="role" class="form-control"><option value="">Semua peran</option>@foreach(['admin' => 'Admin', 'operator' => 'Petugas PTSP', 'user' => 'Masyarakat'] as $value => $label)<option value="{{ $value }}" {{ request('role') === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
        <div class="form-group"><label for="statusFilter" class="form-label">Status</label><select id="statusFilter" name="status" class="form-control"><option value="">Semua status</option><option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option><option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option></select></div>
        <button class="btn btn-secondary" type="submit">Terapkan</button><a href="{{ route('admin.accounts.index') }}" class="btn btn-ghost">Reset</a>
    </form>
    <div class="operations-table-wrap" tabindex="0" role="region" aria-label="Daftar akun pengguna"><table class="data-table operations-table"><thead><tr><th scope="col">Pengguna</th><th scope="col">Peran / akses</th><th scope="col">Satuan kerja</th><th scope="col">Status</th><th scope="col">Tindakan</th></tr></thead><tbody>
    @forelse($accounts as $account)
    <tr><td><strong>{{ $account->name }}</strong><span class="cell-detail">{{ $account->email }}</span></td><td>{{ ['admin'=>'Admin', 'operator'=>'Petugas PTSP', 'user'=>'Masyarakat'][$account->role] ?? $account->role }}@if($account->can_monitor)<span class="cell-detail">{{ $account->canManageMasterData() ? 'Administrasi pusat' : 'Monitoring lintas satker' }}</span>@endif</td><td>{{ $account->work_unit ?: 'Belum ditempatkan' }}</td><td><span class="badge {{ $account->is_active ? 'badge-success' : 'badge-neutral' }}">{{ $account->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td>@if(!$account->hasComplaintAssignment() || auth()->user()->canManageComplaintAccess())<a class="btn btn-secondary btn-sm" href="{{ route('admin.accounts.edit', $account) }}" aria-label="Ubah akun {{ $account->name }}">Ubah akun</a>@else<span class="field-hint">Dikelola admin PTA</span>@endif</td></tr>
    @empty<tr><td colspan="5"><div class="empty-state"><i class="fas fa-users" aria-hidden="true"></i><h3>Akun tidak ditemukan</h3><p>Coba kata kunci atau filter lain.</p><a href="{{ route('admin.accounts.index') }}" class="btn btn-secondary">Reset filter</a></div></td></tr>@endforelse
    </tbody></table></div>
    @include('operations.partials.pagination', ['paginator' => $accounts])
</div>
@endsection
