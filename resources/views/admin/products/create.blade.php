@extends('layouts.admin')

@section('title', __('admin.products.create_title'))
@section('page_title', __('admin.products.create_title'))
@section('page_subtitle', __('admin.products.create_subtitle'))

@section('content')
    <div class="mb-5">
        <a href="{{ route('admin.products.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-brand-800">
            <svg class="h-4 w-4 rtl:-scale-x-100" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a1 1 0 0 1-1 1H7.4l3.3 3.3a1 1 0 0 1-1.4 1.4l-5-5a1 1 0 0 1 0-1.4l5-5a1 1 0 0 1 1.4 1.4L7.4 9H16a1 1 0 0 1 1 1Z" clip-rule="evenodd"/></svg>
            {{ __('common.back') }}
        </a>
    </div>

    @include('admin.products._form')
@endsection
