@extends('layouts.app')

@section('title', __('auth.register_title'))

@section('content')
    <div class="container-page max-w-2xl py-12 lg:py-16">
        <div class="card p-6 sm:p-8">
            <h1 class="text-2xl font-extrabold text-slate-900">{{ __('auth.register_title') }}</h1>
            <p class="mt-1.5 text-sm text-slate-500">{{ __('auth.register_subtitle') }}</p>

            <form method="POST" action="{{ route('register.store') }}" class="mt-6 space-y-4">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="name" class="label">{{ __('fields.name') }}</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name"
                               class="input @error('name') input-error @enderror">
                        @error('name')<p class="mt-1.5 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="phone" class="label">{{ __('fields.phone') }}</label>
                        <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" required autocomplete="tel" dir="ltr"
                               class="input @error('phone') input-error @enderror" placeholder="{{ config('matjar.phone.example') }}">
                        @error('phone')<p class="mt-1.5 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div>
                    <label for="email" class="label">{{ __('fields.email') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" dir="ltr"
                           class="input @error('email') input-error @enderror">
                    @error('email')<p class="mt-1.5 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="password" class="label">{{ __('fields.password') }}</label>
                        <input id="password" name="password" type="password" required autocomplete="new-password"
                               class="input @error('password') input-error @enderror">
                        @error('password')<p class="mt-1.5 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="label">{{ __('fields.password_confirmation') }}</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="input">
                    </div>
                </div>
                <p class="text-xs text-slate-500">{{ __('auth.password_hint') }}</p>
                <button type="submit" class="btn-primary w-full">{{ __('auth.create_account') }}</button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-600">
                {{ __('auth.have_account') }}
                <a href="{{ route('login') }}" class="link">{{ __('auth.sign_in') }}</a>
            </p>
        </div>
    </div>
@endsection
