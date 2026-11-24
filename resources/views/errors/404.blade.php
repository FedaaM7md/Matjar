@php
    $locale = app()->getLocale();
    $dir = config('matjar.locales.'.$locale.'.dir', 'rtl');
    $code = '404';
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ __('errors.404.title') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-50 p-6">
    <main class="card w-full max-w-md p-8 text-center">
        <x-logo class="mx-auto h-14 w-14" />
        <p class="mt-4 text-5xl font-extrabold text-brand-900">404</p>
        <h1 class="mt-2 text-xl font-extrabold text-slate-900">{{ __('errors.404.title') }}</h1>
        <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ __('errors.404.body') }}</p>
        <a href="{{ url('/') }}" class="btn-primary mt-6">{{ __('errors.back_home') }}</a>
    </main>
</body>
</html>
