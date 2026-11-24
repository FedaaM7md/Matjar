@extends('layouts.admin')

@section('title', $category->name_ar)
@section('page_title', $category->name_ar)
@section('page_subtitle', __('admin.categories.edit_title'))

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.categories.index') }}" class="text-sm font-semibold text-slate-500 hover:text-brand-800">&larr; {{ __('common.back') }}</a>
        <a href="{{ route('shop.category', $category->slug) }}" target="_blank" rel="noopener" class="btn-outline btn-sm">{{ __('admin.products.preview') }}</a>
    </div>
    @include('admin.categories._form')

    @can('products.manage')
        <div class="mt-6 max-w-3xl rounded-none border border-red-200 bg-red-50 p-5">
            <h2 class="font-bold text-red-900">{{ __('admin.categories.danger_zone') }}</h2>
            <p class="mt-1 text-sm text-red-800">{{ __('admin.categories.delete_hint') }}</p>
            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="mt-3" x-data="confirmForm" data-message="{{ __('admin.categories.confirm_delete') }}" @submit="onConfirm($event)">
                @csrf @method('DELETE')
                <button type="submit" class="btn-danger btn-sm">{{ __('common.delete') }}</button>
            </form>
        </div>
    @endcan
@endsection
