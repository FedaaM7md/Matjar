@props(['status'])

@php
    $classes = [
        'unpaid' => 'badge-warning',
        'pending' => 'badge-warning',
        'paid' => 'badge-success',
        'failed' => 'badge-danger',
        'cancelled' => 'badge-neutral',
    ];
@endphp

<span {{ $attributes->merge(['class' => $classes[$status] ?? 'badge-neutral']) }}>{{ __('orders.payment_statuses.'.$status) }}</span>
