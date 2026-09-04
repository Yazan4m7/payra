<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale()==='ar'?'rtl':'ltr' }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">@vite(['resources/css/app.scss','resources/js/app.js'])<title>{{ __('hr.login') }}</title></head>
<body class="bg-body-tertiary"><div class="container py-5">
    <div class="d-flex justify-content-center mb-3"><a class="btn btn-sm btn-outline-secondary" href="{{ route('locale', app()->getLocale()==='ar' ? 'en' : 'ar') }}">{{ app()->getLocale()==='ar' ? 'English' : 'العربية' }}</a></div>
    <div class="card shadow-sm mx-auto" style="max-width:420px"><div class="card-body p-4">
        <h1 class="h4 mb-1">{{ tenant('name') }}</h1><p class="text-muted mb-4">{{ __('hr.app_name') }}</p>
        <form method="POST" action="{{ route('login.store') }}">@csrf
            <div class="mb-3"><label class="form-label">{{ __('hr.email') }}</label><input class="form-control" type="email" name="email" value="{{ old('email') }}" required autofocus>@error('email')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            <div class="mb-3"><label class="form-label">{{ __('hr.password') }}</label><input class="form-control" type="password" name="password" required></div>
            <label class="form-check mb-3"><input class="form-check-input" type="checkbox" name="remember" value="1"><span class="form-check-label">{{ __('hr.remember_me') }}</span></label>
            <button class="btn btn-primary w-100">{{ __('hr.login') }}</button>
        </form>
    </div></div>
</div></body></html>
