@extends('layouts.app') 

@section('title', 'Lengkapi Data')

@section('styles')
<style>
    .register-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        padding: 20px;
    }
    .register-card {
        background: var(--surface);
        backdrop-filter: blur(10px);
        border: 1px solid var(--glass-border);
        border-radius: 20px;
        padding: 40px 30px;
        width: 100%;
        max-width: 450px;
        box-shadow: var(--glass-shadow);
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: var(--text-muted);
        font-weight: 500;
    }
    .form-control {
        width: 100%;
        padding: 12px 15px;
        border-radius: 10px;
        background: var(--surface);
        border: 1px solid var(--glass-border);
        color: var(--text);
        margin-bottom: 20px;
        transition: all 0.3s ease;
    }
    .form-control:focus {
        background: var(--surface-muted);
        border-color: var(--primary);
        outline: none;
    }
    .btn-submit {
        justify-content: center;
        width: 100%;
        padding: 14px;
        border-radius: 10px;
        font-weight: 600;
        background: var(--primary);
        border: none;
        color: var(--on-primary);
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(var(--primary-rgb), 0.4);
    }
</style>
@endsection

@section('content')
<div class="register-container">
    <div class="register-card animate__animated animate__fadeIn">
        <h2 style="text-align: center; margin-bottom: 30px;">Lengkapi Data</h2>
        
        <form action="{{ route('guest.register') }}" method="POST">
            @csrf
            
            <div class="form-group">
                <label>Satuan Kerja</label>
                <select name="work_unit" class="form-control" required>
                    <option value="">Pilih Satuan Kerja</option>
                    @foreach($workUnits as $unit)
                        <option value="{{ $unit }}" {{ old('work_unit') === $unit ? 'selected' : '' }}>{{ $unit }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Nomor HP</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" readonly style="cursor: not-allowed;">
            </div>

            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="name" class="form-control" placeholder="Masukkan nama Anda" required value="{{ old('name') }}">
            </div>

            <button type="submit" class="btn btn-submit">
                Simpan & Masuk <i class="fas fa-check-circle" style="margin-left: 8px;"></i>
            </button>
        </form>
    </div>
</div>
@endsection

