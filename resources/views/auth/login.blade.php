<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>Login - {{ config('ui.brand.full_name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/product-ui.css') }}?v={{ filemtime(public_path('css/product-ui.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/illustrated-portal.css') }}?v={{ filemtime(public_path('css/illustrated-portal.css')) }}">
<link rel="icon" type="image/png" sizes="64x64" href="{{ asset('images/'.config('ui.brand.favicon')) }}">
<link rel="apple-touch-icon" href="{{ asset('images/'.config('ui.brand.touch_icon')) }}">
<link rel="stylesheet" href="{{ asset('css/pace-brand.css') }}?v={{ filemtime(public_path('css/pace-brand.css')) }}"></head>
<body class="auth-page">
    

    <div class="auth-container auth-split">
        <section class="auth-brand" aria-label="Tentang PACE PTA Papua Barat">
            <a class="auth-brand-link" href="{{ route('landing') }}">@include('partials.brand')</a>
            <h1>Pelayanan dekat.<br><span>Meski dari jauh.</span></h1>
            <p>Layani masyarakat dari satu ruang kerja.</p>
            @include('public.partials.illustration',['scene'=>'hero','priority'=>true,'illustrationClass'=>'auth-service-art'])
        </section>
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo pace-logo-only">
                    <img src="{{ asset('images/'.config('ui.brand.mark')) }}" width="50" height="50" alt="PACE">
                </div>
                <p class="eyebrow">Akses petugas &amp; pengguna</p><h2>Masuk ke ruang layanan</h2>
                <p>Gunakan email dan kata sandi akun Anda.</p>
            </div>

            <form novalidate method="POST" action="{{ route('login') }}">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="loginEmail">Email</label>
                    <input type="email" id="loginEmail" name="email" autocomplete="username" class="form-control" placeholder="email@example.com" value="{{ old('email') }}" required autofocus>
                    @error('email')
                        <span style="color: var(--danger); font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="loginPassword">Kata sandi</label>
                    <input type="password" id="loginPassword" name="password" autocomplete="current-password" class="form-control" placeholder="••••••••" required>
                    @error('password')
                        <span style="color: var(--danger); font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group" style="display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }} style="accent-color: var(--primary);">
                    <label for="remember" style="font-size: 13px; color: var(--text-muted); cursor: pointer;">Ingat saya</label>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; justify-content: center;">
                    <i class="fas fa-sign-in-alt" aria-hidden="true"></i> Masuk
                </button>
            </form>

            <div class="auth-footer">
                Masyarakat? <a href="{{ route('public.consult') }}">Konsultasi tanpa akun</a><br><a class="auth-home-link" href="{{ route('landing') }}">Kembali ke beranda</a>
            </div>
        </div>
    </div>

    
    <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
    <script src="{{ asset('js/ui.js') }}?v={{ filemtime(public_path('js/ui.js')) }}"></script>
</body>
</html>
