<!DOCTYPE html>
<html lang="id" data-theme="light"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="color-scheme" content="light"><meta name="csrf-token" content="{{ csrf_token() }}"><meta name="robots" content="{{ request()->routeIs('public.ticket*','public.invitation*','public.cases*','public.complaints.*') ? 'noindex, nofollow' : 'index, follow' }}"><meta name="referrer" content="no-referrer"><title>@yield('title', 'Layanan masyarakat') · {{ config('ui.brand.full_name') }}</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"><link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}"><link rel="stylesheet" href="{{ asset('css/public-portal.css') }}?v={{ filemtime(public_path('css/public-portal.css')) }}">@if(request()->routeIs('public.complaints.*'))<link rel="stylesheet" href="{{ asset('css/complaints.css') }}?v={{ filemtime(public_path('css/complaints.css')) }}">@endif<link rel="stylesheet" href="{{ asset('css/product-ui.css') }}?v={{ filemtime(public_path('css/product-ui.css')) }}"><link rel="stylesheet" href="{{ asset('css/illustrated-portal.css') }}?v={{ filemtime(public_path('css/illustrated-portal.css')) }}"><link rel="icon" type="image/png" sizes="64x64" href="{{ asset('images/'.config('ui.brand.favicon')) }}">
<link rel="apple-touch-icon" href="{{ asset('images/'.config('ui.brand.touch_icon')) }}">
<link rel="stylesheet" href="{{ asset('css/pace-brand.css') }}?v={{ filemtime(public_path('css/pace-brand.css')) }}"></head>
<body class="citizen-portal {{ request()->routeIs('landing','public.finder') ? 'citizen-home' : 'citizen-interior' }}"><a class="skip-link" href="#mainContent">Lewati ke konten</a>
<div class="pace-header-slot"><header class="pace-header" data-pace-header><div class="pace-header-inner">
<a class="pace-brand-home" href="{{ route('landing') }}" aria-label="PACE PTA Papua Barat — Beranda">@include('partials.brand')</a>
<div class="pace-header-actions"><a class="pace-staff-link" href="{{ route('login') }}"><i class="far fa-user" aria-hidden="true"></i><span>Login petugas</span></a><button class="pace-menu-toggle" type="button" data-navigation-toggle aria-controls="paceNavigation" aria-expanded="false" aria-label="Buka menu layanan" hidden><i class="fas fa-bars" aria-hidden="true"></i></button></div>
<nav id="paceNavigation" class="pace-navigation" aria-label="Navigasi layanan publik">
@include('public.partials.service-links',['withDescriptions'=>false,'preserveContext'=>false])
@auth<a href="{{ route('dashboard') }}">Ruang kerja</a>@endauth
</nav></div></header></div>
<main id="mainContent" class="citizen-main" tabindex="-1">
@if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
@include('operations.partials.errors')
@yield('content')
</main><footer class="citizen-footer pace-footer">@include('partials.brand')</footer>
<script src="{{ asset('js/ui.js') }}?v={{ filemtime(public_path('js/ui.js')) }}" defer></script><script src="{{ asset('js/public-portal.js') }}?v={{ filemtime(public_path('js/public-portal.js')) }}" defer></script><script src="{{ asset('js/navigation.js') }}?v={{ filemtime(public_path('js/navigation.js')) }}" defer></script>@yield('scripts')</body></html>
