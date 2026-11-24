@extends('layouts.admin')

@section('title', __('inventory.history_title', ['sku' => $variant->sku]))
@section('page_title', __('inventory.history_title', ['sku' => $variant->sku]))
@section('page_subtitle', $variant->product->name_ar)

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.inventory.index') }}" class="text-sm font-semibold text-slate-500 hover:text-brand-800">&larr; {{ __('inventory.back_to_inventory') }}</a>
        <a href="{{ route('admin.products.edit', $variant->product) }}" class="btn-outline btn-sm">{{ __('inventory.edit_product') }}</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card p-4">
            <p class="text-xs font-semibold text-slate-500">{{ __('inventory.on_hand') }}</p>
            <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ $variant->stock }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-semibold text-slate-500">{{ __('inventory.reserved') }}</p>
            <p class="mt-1 text-2xl font-extrabold text-amber-700">{{ $variant->reserved }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-semibold text-slate-500">{{ __('inventory.available') }}</p>
            <p class="mt-1 text-2xl font-extrabold {{ $variant->available_stock <= 0 ? 'text-red-700' : 'text-emerald-700' }}">{{ $variant->available_stock }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-semibold text-slate-500">{{ __('inventory.threshold') }}</p>
            <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ $variant->low_stock_threshold }}</p>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)]">
        <div class="space-y-4">
            <div class="card p-5">
                <h2 class="font-bold text-slate-900">{{ __('inventory.variant_details') }}</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('inventory.product') }}</dt><dd class="font-semibold text-slate-800">{{ $variant->product->name_ar }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('inventory.sku') }}</dt><dd class="font-mono text-xs font-semibold text-slate-800" dir="ltr">{{ $variant->sku }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('inventory.variant') }}</dt><dd class="font-semibold text-slate-800">{{ $variant->label }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('fields.price') }}</dt><dd class="font-semibold text-slate-800" dir="ltr">{{ \App\Support\Money::withCurrency($variant->price_cents) }}</dd></div>
                </dl>
            </div>

            @can('inventory.manage')
                <div class="card p-5" x-data="{ type: 'in' }">
                    <h2 class="font-bold text-slate-900">{{ __('inventory.adjust_title') }}</h2>
                    <p class="mt-1 text-xs text-slate-500">{{ __('inventory.adjust_hint') }}</p>

                    <form method="POST" action="{{ route('admin.inventory.adjust', $variant) }}" class="mt-4 space-y-3">
                        @csrf
                        <div>
                            <label for="type" class="label">{{ __('inventory.type') }}</label>
                            <select id="type" name="type" x-model="type" class="input">
                                <option value="in">{{ __('inventory.types.in') }}</option>
                                <option value="out">{{ __('inventory.types.out') }}</option>
                                <option value="adjust">{{ __('inventory.types.adjust') }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="quantity" class="label" x-text="type === 'adjust' ? '{{ __('inventory.new_stock_count') }}' : '{{ __('inventory.quantity') }}'"></label>
                            <input id="quantity" name="quantity" type="number" min="0" value="{{ old('quantity', 1) }}" required dir="ltr" class="input @error('quantity') input-error @enderror">
                            @error('quantity')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="reason" class="label">{{ __('inventory.reason') }}</label>
                            <input id="reason" name="reason" value="{{ old('reason') }}" maxlength="190" class="input" placeholder="{{ __('inventory.reason_placeholder') }}">
                        </div>
                        <button type="submit" class="btn-primary w-full">{{ __('inventory.apply_adjustment') }}</button>
                    </form>
                    <p class="mt-3 rounded-none bg-brand-50 p-2.5 text-[11px] leading-relaxed text-brand-900">{{ __('inventory.adjust_note') }}</p>
                </div>
            @endcan
        </div>

        <div class="card overflow-hidden">
            <div class="border-b border-slate-100 p-5">
                <h2 class="font-bold text-slate-900">{{ __('inventory.movements') }}</h2>
                <p class="mt-1 text-xs text-slate-500">{{ __('inventory.movements_hint') }}</p>
            </div>
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr>
                            <th>{{ __('inventory.movement_type') }}</th>
                            <th class="text-center">{{ __('inventory.stock_change') }}</th>
                            <th class="text-center">{{ __('inventory.reserved_change') }}</th>
                            <th class="text-center">{{ __('inventory.balance_after') }}</th>
                            <th>{{ __('inventory.reason') }}</th>
                            <th>{{ __('inventory.date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($movements as $movement)
                            @php
                                $typeClass = match ($movement->type) {
                                    'in', 'restore' => 'text-emerald-700',
                                    'out', 'commit' => 'text-red-700',
                                    'reserve', 'release' => 'text-amber-700',
                                    default => 'text-brand-800',
                                };
                                $reasonKey = 'inventory.reasons.'.$movement->reason;
                            @endphp
                            <tr>
                                <td class="font-semibold {{ $typeClass }}">
                                    <span class="block">{{ $movement->type_label }}</span>
                                    @if ($movement->reference_id)
                                        <span class="text-[10px] font-normal text-slate-400" dir="ltr">#{{ $movement->reference_id }}</span>
                                    @endif
                                </td>
                                <td class="text-center font-mono text-xs {{ $movement->stock_change > 0 ? 'text-emerald-700' : ($movement->stock_change < 0 ? 'text-red-700' : 'text-slate-400') }}" dir="ltr">
                                    {{ $movement->stock_change > 0 ? '+' : '' }}{{ $movement->stock_change }}
                                </td>
                                <td class="text-center font-mono text-xs {{ $movement->reserved_change > 0 ? 'text-amber-700' : ($movement->reserved_change < 0 ? 'text-emerald-700' : 'text-slate-400') }}" dir="ltr">
                                    {{ $movement->reserved_change > 0 ? '+' : '' }}{{ $movement->reserved_change }}
                                </td>
                                <td class="text-center text-xs text-slate-600" dir="ltr">{{ $movement->stock_after }} / {{ $movement->reserved_after }}</td>
                                <td class="text-xs text-slate-500">{{ $movement->reason ? (\Illuminate\Support\Facades\Lang::has($reasonKey) ? __($reasonKey) : $movement->reason) : '—' }}</td>
                                <td class="whitespace-nowrap text-xs text-slate-500">
                                    {{ $movement->created_at?->format('Y-m-d H:i') }}
                                    @if ($movement->user)
                                        <span class="block text-[10px] text-slate-400">{{ $movement->user->name }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">{{ __('inventory.no_movements') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $movements->links() }}</div>
        </div>
    </div>
@endsection
