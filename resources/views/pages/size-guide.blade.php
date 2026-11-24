@extends('layouts.app')

@section('title', __('pages.size_guide.title'))
@section('meta_description', __('pages.size_guide.meta'))

@section('content')
    <div class="container-page max-w-3xl py-10 lg:py-16">
        <p class="eyebrow">{{ __('common.app_tagline') }}</p>
        <h1 class="mt-3 font-display text-3xl font-extrabold text-brand-950 sm:text-4xl">{{ __('pages.size_guide.title') }}</h1>
        <p class="mt-4 text-base leading-relaxed text-stone-700">{{ __('pages.size_guide.intro') }}</p>

        <div class="mt-8 overflow-x-auto border border-stone-200">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>{{ __('pages.size_guide.table.size') }}</th>
                        <th class="text-center">{{ __('pages.size_guide.table.chest') }}</th>
                        <th class="text-center">{{ __('pages.size_guide.table.waist') }}</th>
                        <th class="text-center">{{ __('pages.size_guide.table.length') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (__('pages.size_guide.sizes') as $row)
                        <tr>
                            <td class="font-display font-extrabold text-brand-950" dir="ltr">{{ $row['label'] }}</td>
                            <td class="text-center num" dir="ltr">{{ $row['chest'] }} {{ __('pages.size_guide.unit') }}</td>
                            <td class="text-center num" dir="ltr">{{ $row['waist'] }} {{ __('pages.size_guide.unit') }}</td>
                            <td class="text-center num" dir="ltr">{{ $row['length'] }} {{ __('pages.size_guide.unit') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <section class="mt-8 border-s-4 border-accent-500 bg-accent-50 p-5">
            <h2 class="font-display text-lg font-extrabold text-brand-950">{{ __('pages.size_guide.measure_title') }}</h2>
            <p class="mt-2 text-sm leading-relaxed text-accent-900">{{ __('pages.size_guide.measure_body') }}</p>
        </section>

        <div class="mt-8">
            <a href="{{ url('/shop') }}" class="btn-primary min-h-11">{{ __('home.cta_shop') }}</a>
        </div>
    </div>
@endsection
