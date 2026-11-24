@extends('layouts.admin')

@section('title', __('admin.coupons.create_title'))
@section('page_title', __('admin.coupons.create_title'))
@section('page_subtitle', __('admin.coupons.subtitle'))

@section('content')
    <div class="mb-5"><a href="{{ route('admin.coupons.index') }}" class="text-sm font-semibold text-slate-500 hover:text-brand-800">&larr; {{ __('common.back') }}</a></div>
    @include('admin.coupons._form')
@endsection
