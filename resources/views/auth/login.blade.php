@extends('layouts.app')

@section('title', __('auth.login_title'))

@section('content')
    <div class="container-page grid max-w-5xl gap-0 border border-stone-200 py-0 lg:grid-cols-2">
        <div class="hidden bg-brand-950 p-10 text-white lg:flex lg:flex-col lg:justify-between">
            <div>
                <x-logo class="h-12 w-12 text-white" />
                <h2 class="mt-8 font-display text-3xl font-extrabold leading-tight text-white">{{ __('auth.aside_title') }}</h2>
                <p class="mt-3 text-sm leading-relaxed text-brand-200">{{ __('auth.aside_body') }}</p>
            </div>
            <ul class="mt-10 space-y-3 text-sm text-brand-100">
                @foreach (['auth.aside_point_1', 'auth.aside_point_2', 'auth.aside_point_3'] as $point)
                    <li class="flex items-start gap-2.5">
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-accent-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.7-9.3a1 1 0 0 0-1.4-1.4L9 10.6 7.7 9.3a1 1 0 0 0-1.4 1.4l2 2a1 1 0 0 0 1.4 0l4-4Z" clip-rule="evenodd"/></svg>
                        {{ __($point) }}
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="p-6 sm:p-10">
            <h1 class="font-display text-2xl font-extrabold text-brand-950">{{ __('auth.login_title') }}</h1>
            <p class="mt-1.5 text-sm text-stone-600">{{ __('auth.login_subtitle') }}</p>

            @if (config('matjar.demo.enabled'))
                <div class="mt-6 border border-stone-200 bg-paper/60 p-4">
                    <p class="flex flex-wrap items-center gap-2 text-sm font-bold text-brand-950">
                        {{ __('demo.launch') }}
                        <span class="text-xs font-normal text-stone-500">{{ __('demo.launch_hint') }}</span>
                    </p>
                    <div class="mt-3 grid grid-cols-3 gap-2">
                        @foreach ((array) config('matjar.demo.roles') as $key => $role)
                            <form method="POST" action="{{ route('demo.login') }}">
                                @csrf
                                <input type="hidden" name="role" value="{{ $key }}">
                                <button type="submit" class="btn-outline btn-sm w-full">{{ __($role['label']) }}</button>
                            </form>
                        @endforeach
                    </div>
                </div>
                <div class="mt-6 flex items-center gap-3 text-xs font-semibold text-stone-400">
                    <span class="h-px flex-1 bg-stone-200"></span>
                    {{ __('auth.or') }}
                    <span class="h-px flex-1 bg-stone-200"></span>
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="email" class="label">{{ __('fields.email') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" dir="ltr"
                           class="input @error('email') input-error @enderror" placeholder="name@example.com">
                    @error('email')<p class="mt-1.5 text-xs font-semibold text-accent-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password" class="label">{{ __('fields.password') }}</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password"
                           class="input @error('password') input-error @enderror">
                    @error('password')<p class="mt-1.5 text-xs font-semibold text-accent-600">{{ $message }}</p>@enderror
                </div>
                <div class="flex items-center justify-between">
                    <label class="inline-flex items-center gap-2 text-sm text-stone-600">
                        <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded-none border-stone-300 text-brand-900 focus:ring-brand-900">
                        {{ __('fields.remember') }}
                    </label>
                </div>
                <button type="submit" class="btn-primary w-full">{{ __('auth.sign_in') }}</button>
            </form>

            <p class="mt-6 text-center text-sm text-stone-600">
                {{ __('auth.no_account') }}
                <a href="{{ route('register') }}" class="link">{{ __('auth.create_account') }}</a>
            </p>
        </div>
    </div>
@endsection
