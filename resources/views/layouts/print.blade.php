@php
    $storeName = \App\Models\Setting::get('store_name_'.$locale, __('common.app_name'));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $textDir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title', $storeName)</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-100">
<div class="no-print sticky top-0 z-10 border-b border-slate-200 bg-white">
    <div class="mx-auto flex max-w-3xl items-center justify-between gap-3 px-4 py-3">
        <a href="{{ url('/') }}" class="flex items-center gap-2 text-sm font-bold text-brand-900">
            <x-logo class="h-8 w-8" />
            {{ __('common.app_name') }}
        </a>
        <div class="flex items-center gap-2">
            @yield('actions')
            <button type="button" x-data="printPage" @click="print()" class="btn-primary btn-sm">{{ __('orders.print_invoice') }}</button>
        </div>
    </div>
</div>
<main class="mx-auto my-6 max-w-3xl bg-white p-6 shadow-sm print:my-0 print:max-w-none print:p-0 print:shadow-none">
    @yield('content')
</main>
</body>
</html>
