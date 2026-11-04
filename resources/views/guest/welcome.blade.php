@extends('layouts.app') 

@section('title', 'Selamat Datang')

@section('styles')
<style>
    .welcome-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        padding: 20px;
    }
    .welcome-card {
        background: var(--surface);
        backdrop-filter: blur(10px);
        border: 1px solid var(--glass-border);
        border-radius: 20px;
        padding: 40px;
        width: 100%;
        max-width: 400px;
        text-align: center;
        box-shadow: var(--glass-shadow);
    }
    .welcome-logo {
        font-size: 48px;
        color: var(--primary);
        margin-bottom: 20px;
    }
    .form-control-lg {
        height: 50px;
        font-size: 18px;
        border-radius: 10px;
        background: var(--surface-muted);
        color: var(--text);
        border: 1px solid var(--glass-border);
        text-align: center;
        margin-bottom: 20px;
    }
    select.form-control-lg {
        padding: 0 42px 0 14px;
    }
    .btn-submit {
        justify-content: center;
        width: 100%;
        height: 50px;
        border-radius: 10px;
        font-size: 18px;
        font-weight: 600;
        background: var(--primary);
        border: none;
        color: var(--on-primary);
        transition: all 0.3s ease;
    }
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(var(--primary-rgb), 0.4);
    }
    .operator-link {
        display: block;
        margin-top: 20px;
        color: var(--text-muted);
        font-size: 14px;
        text-decoration: none;
    }
    .operator-link:hover {
        color: var(--primary-light);
    }
</style>
@endsection

@section('content')
<div class="welcome-container">
    <div class="welcome-card animate__animated animate__fadeInUp">
        <div style="margin-bottom:24px">@include('partials.brand')</div>
        <p style="color: var(--text-muted); margin-bottom: 30px;">Pelayanan Terpadu Satu Pintu</p>

        @if($errors->any())
            <div class="alert alert-danger" style="margin-bottom: 20px; text-align: left; font-size: 14px;">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('guest.check') }}" method="POST">
            @csrf

            <div class="form-group">
                <select name="work_unit" class="form-control form-control-lg" required>
                    <option value="">Pilih Satuan Kerja</option>
                    @foreach($workUnits as $unit)
                        <option value="{{ $unit }}" {{ old('work_unit') === $unit ? 'selected' : '' }}>{{ $unit }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="form-group">
                <input type="number" name="phone" class="form-control form-control-lg" placeholder="Masukkan Nomor HP" required autofocus value="{{ old('phone') }}">
            </div>

            <button type="submit" class="btn btn-submit">
                Masuk <i class="fas fa-arrow-right" style="margin-left: 8px;"></i>
            </button>
        </form>

        <a href="{{ route('login') }}" class="operator-link">
            <i class="fas fa-user-shield"></i> Login Petugas / Admin
        </a>
    </div>
</div>
@endsection

