<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>Daftar - {{ config('ui.brand.full_name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
<link rel="stylesheet" href="{{ asset('css/product-ui.css') }}?v={{ filemtime(public_path('css/product-ui.css')) }}"><link rel="icon" type="image/png" sizes="64x64" href="{{ asset('images/'.config('ui.brand.favicon')) }}">
<link rel="apple-touch-icon" href="{{ asset('images/'.config('ui.brand.touch_icon')) }}">
<link rel="stylesheet" href="{{ asset('css/pace-brand.css') }}?v={{ filemtime(public_path('css/pace-brand.css')) }}"></head>
<body class="auth-page">
    
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo pace-logo-only"><img src="{{ asset('images/'.config('ui.brand.mark')) }}" width="50" height="50" alt="PACE"></div>
                <h2>Buat Akun Baru</h2>
                <p>Daftar untuk mengakses layanan PTSP</p>
            </div>
            <form novalidate method="POST" action="{{ route('register') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="name" class="form-control" placeholder="Nama lengkap" value="{{ old('name') }}" required>
                    @error('name')<span style="color:var(--danger);font-size:12px;display:block;margin-top:4px;">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="email@example.com" value="{{ old('email') }}" required>
                    @error('email')<span style="color:var(--danger);font-size:12px;display:block;margin-top:4px;">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">No. Telepon</label>
                    <input type="text" name="phone" class="form-control" placeholder="08xxxxxxxxxx" value="{{ old('phone') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="registerWorkUnit">Satuan Kerja</label>
                    <select id="registerWorkUnit" name="work_unit" class="form-control" required>
                        <option value="">Pilih Satuan Kerja</option>
                        @foreach(\App\WorkUnit::activeNames() as $unit)
                        <option value="{{ $unit }}" {{ old('work_unit') === $unit ? 'selected' : '' }}>{{ $unit }}</option>
                        @endforeach
                    </select>
                    @error('work_unit')<span style="color:var(--danger);font-size:12px;display:block;margin-top:4px;">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Min. 8 karakter" required>
                    @error('password')<span style="color:var(--danger);font-size:12px;display:block;margin-top:4px;">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Konfirmasi Password</label>
                    <input type="password" name="password_confirmation" class="form-control" placeholder="Ulangi password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center;">
                    <i class="fas fa-user-plus"></i> Daftar
                </button>
            </form>
            <div class="auth-footer">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></div>
        </div>
    </div>
    
    <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
    <script src="{{ asset('js/ui.js') }}?v={{ filemtime(public_path('js/ui.js')) }}"></script>
</body>
</html>
