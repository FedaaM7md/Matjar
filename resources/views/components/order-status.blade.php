@props(['status'])

@php
    $classes = [
        'pending' => 'badge-warning',
        'confirmed' => 'badge-info',
        'shipped' => 'badge-info',
        'delivered' => 'badge-success',
        'cancelled' => 'badge-danger',
        'returned' => 'badge-neutral',
    ];
@endphp

<span {{ $attributes->merge(['class' => $classes[$status] ?? 'badge-neutral']) }}>{{ __('orders.statuses.'.$status) }}</span>
