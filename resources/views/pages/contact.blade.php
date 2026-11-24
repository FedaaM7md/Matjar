@extends('layouts.app')

@section('title', __('pages.contact.title'))
@section('meta_description', __('pages.contact.meta'))

@section('content')
    <div class="container-page max-w-3xl py-10 lg:py-16">
        <p class="eyebrow">{{ __('common.app_tagline') }}</p>
        <h1 class="mt-3 font-display text-3xl font-extrabold text-brand-950 sm:text-4xl">{{ __('pages.contact.title') }}</h1>
        <p class="mt-4 text-base leading-relaxed text-stone-700">{{ __('pages.contact.intro') }}</p>

        <dl class="mt-8 grid gap-px border border-stone-200 bg-stone-200 sm:grid-cols-2">
            <div class="bg-white p-5">
                <dt class="text-xs font-bold text-stone-500">{{ __('pages.contact.phone_label') }}</dt>
                <dd class="mt-1 text-lg font-extrabold text-brand-950"><a href="tel:{{ __('pages.contact.phone') }}" dir="ltr" class="hover:text-accent-600">{{ __('pages.contact.phone') }}</a></dd>
            </div>
            <div class="bg-white p-5">
                <dt class="text-xs font-bold text-stone-500">{{ __('pages.contact.email_label') }}</dt>
                <dd class="mt-1 break-all text-lg font-extrabold text-brand-950"><a href="mailto:{{ __('pages.contact.email') }}" dir="ltr" class="hover:text-accent-600">{{ __('pages.contact.email') }}</a></dd>
            </div>
            <div class="bg-white p-5">
                <dt class="text-xs font-bold text-stone-500">{{ __('pages.contact.address_label') }}</dt>
                <dd class="mt-1 text-sm font-semibold text-stone-800">{{ __('pages.contact.address') }}</dd>
            </div>
            <div class="bg-white p-5">
                <dt class="text-xs font-bold text-stone-500">{{ __('pages.contact.hours_label') }}</dt>
                <dd class="mt-1 text-sm font-semibold text-stone-800">{{ __('pages.contact.hours') }}</dd>
            </div>
        </dl>

        <p class="mt-6 border-s-2 border-amber-400 bg-amber-50 p-3 text-sm leading-relaxed text-amber-900">{{ __('pages.contact.note') }}</p>
    </div>
@endsection
