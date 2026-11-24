@props(['name' => 'default', 'label' => null, 'error' => null])

@switch($name)
    @case('success')
        <span {{ $attributes->merge(['class' => 'badge-success']) }}>{{ $label ?? __('common.active') }}</span>
        @break
    @case('warning')
        <span {{ $attributes->merge(['class' => 'badge-warning']) }}>{{ $label }}</span>
        @break
    @case('danger')
        <span {{ $attributes->merge(['class' => 'badge-danger']) }}>{{ $label }}</span>
        @break
    @case('info')
        <span {{ $attributes->merge(['class' => 'badge-info']) }}>{{ $label }}</span>
        @break
    @default
        <span {{ $attributes->merge(['class' => 'badge-neutral']) }}>{{ $label }}</span>
@endswitch
