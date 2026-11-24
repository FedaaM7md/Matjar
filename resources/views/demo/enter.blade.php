@extends('layouts.app')

@section('title', __('demo.title'))

@section('content')
    <div class="container-page max-w-4xl py-12 lg:py-20">
        <p class="eyebrow">{{ __('demo.badge') }}</p>
        <h1 class="mt-3 text-3xl font-extrabold text-brand-950 sm:text-4xl">{{ __('demo.title') }}</h1>
        <p class="mt-3 max-w-2xl text-base leading-relaxed text-stone-600">{{ __('demo.subtitle') }}</p>

        <div class="mt-8 grid gap-4 sm:grid-cols-3">
            @foreach ($roles as $key => $role)
                <form method="POST" action="{{ route('demo.login') }}" class="h-full">
                    @csrf
                    <input type="hidden" name="role" value="{{ $key }}">
                    <button type="submit"
                            class="group flex h-full w-full flex-col border border-stone-200 bg-white p-6 text-start transition hover:border-brand-900 hover:bg-brand-50">
                        <span class="flex h-12 w-12 items-center justify-center border border-stone-300 text-brand-900 transition group-hover:border-accent-500 group-hover:text-accent-600" aria-hidden="true">
                            @switch($key)
                                @case('admin')
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 3l7 3v5c0 4.4-3 8.3-7 10-4-1.7-7-5.6-7-10V6l7-3Z" stroke-linejoin="round"/><path d="M9 12l2 2 4-4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    @break
                                @case('staff')
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 7h16v13H4V7Zm0 0 8-4 8 4" stroke-linejoin="round"/><path d="M9 12h6" stroke-linecap="round"/></svg>
                                    @break
                                @default
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="8" r="3.5"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0" stroke-linecap="round"/></svg>
                            @endswitch
                        </span>
                        <span class="mt-4 text-lg font-extrabold text-brand-950">{{ __($role['label']) }}</span>
                        <span class="mt-1 flex-1 text-sm leading-relaxed text-stone-600">{{ __('demo.role_'.$key.'_desc') }}</span>
                        <span class="mt-4 inline-flex items-center gap-1.5 text-sm font-bold text-accent-600">
                            {{ __('demo.enter_as', ['role' => __($role['label'])]) }}
                            <svg class="h-4 w-4 rtl:-scale-x-100" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a1 1 0 0 1 1-1h9.6l-3.3-3.3a1 1 0 1 1 1.4-1.4l5 5a1 1 0 0 1 0 1.4l-5 5a1 1 0 0 1-1.4-1.4L13.6 11H4a1 1 0 0 1-1-1Z" clip-rule="evenodd"/></svg>
                        </span>
                    </button>
                </form>
            @endforeach
        </div>

        <p class="mt-6 flex items-start gap-2 border-s-2 border-amber-400 bg-amber-50 px-4 py-3 text-sm leading-relaxed text-amber-900">
            <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm1-5a1 1 0 1 1-2 0V7a1 1 0 1 1 2 0v6Zm-1 3.5a1.1 1.1 0 1 0 0-2.2 1.1 1.1 0 0 0 0 2.2Z" clip-rule="evenodd"/></svg>
            {{ __('demo.note') }}
        </p>

        <div class="mt-8">
            <a href="{{ route('home') }}" class="link text-sm">&larr; {{ __('demo.back_home') }}</a>
        </div>
    </div>
@endsection
