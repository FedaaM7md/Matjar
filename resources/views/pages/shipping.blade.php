@extends('layouts.app')

@section('title', __('pages.shipping.title'))
@section('meta_description', __('pages.shipping.meta'))

@section('content')
    <div class="container-page max-w-3xl py-10 lg:py-16">
        <p class="eyebrow">{{ __('common.app_tagline') }}</p>
        <h1 class="mt-3 font-display text-3xl font-extrabold text-brand-950 sm:text-4xl">{{ __('pages.shipping.title') }}</h1>
        <p class="mt-4 text-base leading-relaxed text-stone-700">{{ __('pages.shipping.intro') }}</p>

        <div class="mt-8 space-y-px border border-stone-200 bg-stone-200">
            @foreach (['delivery', 'rates', 'free', 'returns'] as $section)
                <section class="bg-white p-5 sm:p-6">
                    <h2 class="font-display text-lg font-extrabold text-brand-950">{{ __('pages.shipping.'.$section.'.title') }}</h2>
                    <p class="mt-2 text-sm leading-relaxed text-stone-600">{{ __('pages.shipping.'.$section.'.body') }}</p>
                </section>
            @endforeach
        </div>

        <p class="mt-6 text-sm leading-relaxed text-stone-600">{{ __('pages.shipping.returns_note') }}</p>

        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ url('/shop') }}" class="btn-primary min-h-11">{{ __('home.cta_shop') }}</a>
            <a href="{{ route('pages.size-guide') }}" class="btn-outline min-h-11">{{ __('pages.size_guide.title') }}</a>
            <a href="{{ route('pages.contact') }}" class="btn-ghost min-h-11">{{ __('pages.contact.title') }}</a>
        </div>
    </div>
@endsection
