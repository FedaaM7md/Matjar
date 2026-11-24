@extends('layouts.app')

@section('title', __('account.title'))

@section('content')
    <div class="border-b border-slate-200 bg-white">
        <div class="container-page py-8">
            <p class="eyebrow">{{ __('account.eyebrow') }}</p>
            <div class="mt-2 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 class="text-3xl font-extrabold text-brand-950">{{ __('account.greeting', ['name' => $user->name]) }}</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ $user->email }} · <span dir="ltr">{{ $user->phone }}</span></p>
                </div>
                <a href="{{ route('shop.index') }}" class="btn-outline">{{ __('common.all_products') }}</a>
            </div>
        </div>
    </div>

    <div class="container-page py-8">
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="card p-4">
                <p class="text-xs font-semibold text-slate-500">{{ __('account.stats_orders') }}</p>
                <p class="mt-1 text-2xl font-extrabold text-brand-950">{{ $stats['orders'] }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs font-semibold text-slate-500">{{ __('account.stats_active') }}</p>
                <p class="mt-1 text-2xl font-extrabold text-amber-700">{{ $stats['active'] }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs font-semibold text-slate-500">{{ __('account.stats_spent') }}</p>
                <p class="mt-1 text-2xl font-extrabold text-emerald-700" dir="ltr">{{ \App\Support\Money::withCurrency($stats['spent']) }}</p>
            </div>
        </div>

        <div class="mt-6 grid gap-5 lg:grid-cols-3">
            <div class="card overflow-hidden lg:col-span-2">
                <div class="flex items-center justify-between border-b border-slate-100 p-5">
                    <h2 class="font-bold text-slate-900">{{ __('account.recent_orders') }}</h2>
                    <a href="{{ route('account.orders') }}" class="link text-sm">{{ __('common.view_all') }}</a>
                </div>

                @if ($recentOrders->isEmpty())
                    <div class="p-8 text-center">
                        <p class="text-sm text-slate-500">{{ __('account.no_orders') }}</p>
                        <a href="{{ route('shop.index') }}" class="btn-primary btn-sm mt-4">{{ __('cart.continue_shopping') }}</a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="table-base">
                            <thead>
                                <tr>
                                    <th>{{ __('account.order_number') }}</th>
                                    <th>{{ __('account.date') }}</th>
                                    <th class="text-center">{{ __('account.items_count') }}</th>
                                    <th>{{ __('common.status') }}</th>
                                    <th class="text-end">{{ __('account.total') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentOrders as $order)
                                    <tr>
                                        <td class="font-mono text-xs font-bold text-brand-900" dir="ltr">{{ $order->number }}</td>
                                        <td class="whitespace-nowrap text-xs text-slate-500">{{ $order->placed_at?->format('Y-m-d') }}</td>
                                        <td class="text-center text-sm">{{ $order->items_count }}</td>
                                        <td><x-order-status :status="$order->status" /></td>
                                        <td class="text-end text-sm font-bold" dir="ltr">{{ \App\Support\Money::withCurrency($order->total_cents) }}</td>
                                        <td class="text-end"><a href="{{ route('orders.show', $order->number) }}" class="link text-xs">{{ __('account.view') }}</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="card p-5">
                <h2 class="font-bold text-slate-900">{{ __('account.details') }}</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('fields.name') }}</dt><dd class="font-semibold text-slate-800">{{ $user->name }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('fields.phone') }}</dt><dd class="font-semibold text-slate-800" dir="ltr">{{ $user->phone }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('fields.email') }}</dt><dd class="min-w-0 truncate font-semibold text-slate-800" dir="ltr">{{ $user->email }}</dd></div>
                </dl>
                <div class="mt-5 border-t border-slate-100 pt-4">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn-outline btn-sm w-full">{{ __('common.logout') }}</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="card mt-6 p-5">
            <h2 class="font-bold text-slate-900">{{ __('account.edit_profile') }}</h2>
            @if ($user->isDemo())
                <p class="mt-3 border-s-2 border-amber-400 bg-amber-50 p-3 text-sm leading-relaxed text-amber-900">{{ __('demo.note') }}</p>
            @else
                <form method="POST" action="{{ route('account.profile.update') }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                    @csrf @method('PUT')
                    <div>
                        <label for="profile_name" class="label">{{ __('fields.name') }}</label>
                        <input id="profile_name" name="name" value="{{ old('name', $user->name) }}" required class="input @error('name') input-error @enderror">
                        @error('name')<p class="mt-1 text-xs font-semibold text-accent-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="profile_email" class="label">{{ __('fields.email') }}</label>
                        <input id="profile_email" name="email" type="email" value="{{ old('email', $user->email) }}" required dir="ltr" class="input @error('email') input-error @enderror">
                        @error('email')<p class="mt-1 text-xs font-semibold text-accent-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="profile_password" class="label">{{ __('fields.new_password') }}</label>
                        <input id="profile_password" name="password" type="password" autocomplete="new-password" class="input @error('password') input-error @enderror">
                        <p class="mt-1 text-[11px] text-slate-500">{{ __('account.password_optional_hint') }}</p>
                        @error('password')<p class="mt-1 text-xs font-semibold text-accent-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="profile_password_confirmation" class="label">{{ __('fields.password_confirmation') }}</label>
                        <input id="profile_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="input">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="profile_current_password" class="label">{{ __('fields.current_password') }}</label>
                        <input id="profile_current_password" name="current_password" type="password" autocomplete="current-password" class="input @error('current_password') input-error @enderror">
                        @error('current_password')<p class="mt-1 text-xs font-semibold text-accent-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="btn-primary min-h-11">{{ __('common.save') }}</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endsection
