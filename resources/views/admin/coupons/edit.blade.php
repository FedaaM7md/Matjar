@extends('layouts.admin')

@section('title', __('admin.coupons.edit_title').' '.$coupon->code)
@section('page_title', __('admin.coupons.edit_title').' '.$coupon->code)
@section('page_subtitle', __('admin.coupons.subtitle'))

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.coupons.index') }}" class="text-sm font-semibold text-slate-500 hover:text-brand-800">&larr; {{ __('common.back') }}</a>
        <span class="{{ $coupon->status_label === __('coupons.statuses.active') ? 'badge-success' : 'badge-neutral' }}">{{ $coupon->status_label }}</span>
    </div>
    @include('admin.coupons._form')
@endsection
