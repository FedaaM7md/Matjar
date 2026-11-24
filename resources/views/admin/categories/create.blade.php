@extends('layouts.admin')

@section('title', __('admin.categories.create_title'))
@section('page_title', __('admin.categories.create_title'))
@section('page_subtitle', __('admin.categories.subtitle'))

@section('content')
    <div class="mb-5">
        <a href="{{ route('admin.categories.index') }}" class="text-sm font-semibold text-slate-500 hover:text-brand-800">&larr; {{ __('common.back') }}</a>
    </div>
    @include('admin.categories._form')
@endsection
