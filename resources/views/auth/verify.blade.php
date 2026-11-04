@extends('layouts.app')
@section('title', 'Verifikasi email')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <h1 class="card-header">{{ __('Verifikasi email Anda') }}</h1>

                <div class="card-body">
                    @if (session('resent'))
                        <div class="alert alert-success" role="alert">
                            {{ __('Tautan verifikasi baru telah dikirim ke email Anda.') }}
                        </div>
                    @endif

                    {{ __('Periksa email Anda dan buka tautan verifikasi untuk melanjutkan.') }}
                    {{ __('Jika email belum diterima') }},
                    <form class="d-inline" method="POST" action="{{ route('verification.resend') }}">
                        @csrf
                        <button type="submit" class="btn btn-link p-0 m-0 align-baseline">{{ __('Kirim ulang tautan verifikasi') }}</button>.
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
