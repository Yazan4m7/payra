<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale()==='ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? __('hr.app_name') }}</title>
    @vite(['resources/css/app.scss','resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-body-tertiary">
<div class="d-flex min-vh-100">
    @auth
        <aside class="hr-sidebar p-3 d-none d-lg-block">
            <div class="fw-bold fs-5 mb-1">{{ tenant('name') ?? __('hr.app_name') }}</div>
            <div class="small opacity-75 mb-4">{{ tenant('sector') }}</div>
            @include('components.layouts.partials.tenant-nav')
        </aside>

        <div class="offcanvas offcanvas-start hr-mobile-nav text-bg-dark" tabindex="-1" id="hrMobileNav" aria-labelledby="hrMobileNavLabel">
            <div class="offcanvas-header">
                <div><h5 class="offcanvas-title" id="hrMobileNavLabel">{{ tenant('name') ?? __('hr.app_name') }}</h5><div class="small opacity-75">{{ tenant('sector') }}</div></div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">@include('components.layouts.partials.tenant-nav')</div>
        </div>
    @endauth

    <main class="flex-grow-1 min-w-0">
        @auth
            <header class="bg-white border-bottom px-3 py-2 d-flex justify-content-between align-items-center sticky-top hr-topbar">
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#hrMobileNav" aria-controls="hrMobileNav">☰ <span class="visually-hidden">{{ __('hr.mobile_menu') }}</span></button>
                    <strong>{{ $title ?? __('hr.app_name') }}</strong>
                </div>
                <div class="d-flex align-items-center gap-2 gap-md-3">
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('locale', app()->getLocale()==='ar'?'en':'ar') }}">{{ app()->getLocale()==='ar'?'EN':'عربي' }}</a>
                    <span class="d-none d-md-inline">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-sm btn-outline-secondary">{{ __('hr.logout') }}</button></form>
                </div>
            </header>
        @endauth
        <div class="container-fluid p-3 p-lg-4">
            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
            {{ $slot }}
        </div>
    </main>
</div>
@livewireScripts
</body>
</html>
