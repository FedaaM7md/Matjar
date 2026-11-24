@extends('layouts.app')

@section('title', __('pages.about.title'))
@section('meta_description', __('pages.about.meta'))

@section('content')
    <div class="container-page max-w-3xl py-10 lg:py-16">
        <p class="eyebrow">{{ __('common.app_tagline') }}</p>
        <h1 class="mt-3 font-display text-3xl font-extrabold text-brand-950 sm:text-4xl">{{ __('pages.about.title') }}</h1>
        <p class="mt-4 text-lg leading-relaxed text-stone-700">{{ __('pages.about.intro') }}</p>

        <div class="mt-6 space-y-4 text-base leading-relaxed text-stone-600">
            @foreach (__('pages.about.story') as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach
        </div>

        <div class="mt-10 grid gap-px border border-stone-200 bg-stone-200 sm:grid-cols-3">
            @foreach (__('pages.about.values') as $value)
                <div class="bg-white p-5">
                    <h2 class="font-display text-base font-extrabold text-brand-950">{{ $value['title'] }}</h2>
                    <p class="mt-1.5 text-sm leading-relaxed text-stone-600">{{ $value['body'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-10 flex flex-wrap gap-3">
            <a href="{{ url('/shop') }}" class="btn-primary min-h-11">{{ __('home.cta_shop') }}</a>
            <a href="{{ route('pages.contact') }}" class="btn-outline min-h-11">{{ __('pages.contact.title') }}</a>
        </div>
    </div>
@endsection
