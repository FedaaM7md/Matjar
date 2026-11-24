@extends('layouts.app')

@section('title', __('pages.privacy.title'))
@section('meta_description', __('pages.privacy.meta'))

@section('content')
    <div class="container-page max-w-3xl py-10 lg:py-16">
        <p class="eyebrow">{{ __('common.app_tagline') }}</p>
        <h1 class="mt-3 font-display text-3xl font-extrabold text-brand-950 sm:text-4xl">{{ __('pages.privacy.title') }}</h1>
        <p class="mt-4 text-base leading-relaxed text-stone-700">{{ __('pages.privacy.intro') }}</p>

        <div class="mt-8 space-y-px border border-stone-200 bg-stone-200">
            @foreach (__('pages.privacy.sections') as $section)
                <section class="bg-white p-5 sm:p-6">
                    <h2 class="font-display text-lg font-extrabold text-brand-950">{{ $section['title'] }}</h2>
                    <p class="mt-2 text-sm leading-relaxed text-stone-600">{{ $section['body'] }}</p>
                </section>
            @endforeach
        </div>
    </div>
@endsection
