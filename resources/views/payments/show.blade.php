@extends('layouts.app')

@section('title', __('payments.title'))

@section('content')
    <div class="container-page max-w-2xl py-10">
        <div class="card overflow-hidden">
            <div class="bg-brand-950 p-6 text-white sm:p-8">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-11 w-11 items-center justify-center rounded-none bg-white/10">
                            <svg class="h-6 w-6 text-accent-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18M7 15h3" stroke-linecap="round"/></svg>
                        </span>
                        <div>
                            <p class="text-sm font-extrabold">{{ __('payments.simulator') }}</p>
                            <p class="text-[11px] font-semibold text-accent-300">{{ __('common.app_name') }}</p>
                        </div>
                    </div>
                    <span class="badge-warning">{{ __('common.demo_badge') }}</span>
                </div>

                <p class="mt-4 text-xs leading-relaxed text-brand-200">{{ __('payments.demo_warning') }}</p>

                <dl class="mt-5 grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-none bg-white/10 p-3">
                        <dt class="text-[11px] text-brand-300">{{ __('payments.order') }}</dt>
                        <dd class="mt-0.5 font-mono font-bold" dir="ltr">{{ $order->number }}</dd>
                    </div>
                    <div class="rounded-none bg-white/10 p-3">
                        <dt class="text-[11px] text-brand-300">{{ __('payments.amount') }}</dt>
                        <dd class="mt-0.5 font-extrabold" dir="ltr">{{ \App\Support\Money::withCurrency($payment->amount_cents) }}</dd>
                    </div>
                </dl>
            </div>

            <div class="space-y-4 p-6 sm:p-8">
                <h2 class="font-extrabold text-slate-900">{{ __('payments.choose_outcome') }}</h2>

                <form method="POST" action="{{ route('payments.callback', $payment->reference) }}">
                    @csrf
                    <input type="hidden" name="outcome" value="success">
                    <input type="hidden" name="amount_cents" value="{{ $fields['success']['amount_cents'] }}">
                    <input type="hidden" name="expires" value="{{ $fields['success']['expires'] }}">
                    <input type="hidden" name="signature" value="{{ $fields['success']['signature'] }}">
                    <button type="submit" class="flex w-full items-center justify-between gap-4 rounded-none border border-emerald-300 bg-emerald-50 p-4 text-start transition hover:border-emerald-500 hover:bg-emerald-100">
                        <span>
                            <span class="block font-bold text-emerald-900">{{ __('payments.success') }}</span>
                            <span class="mt-0.5 block text-xs text-emerald-800">{{ __('payments.success_desc') }}</span>
                        </span>
                        <svg class="h-6 w-6 shrink-0 text-emerald-600 rtl:-scale-x-100" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a1 1 0 0 1 1-1h9.6l-3.3-3.3a1 1 0 1 1 1.4-1.4l5 5a1 1 0 0 1 0 1.4l-5 5a1 1 0 0 1-1.4-1.4L13.6 11H4a1 1 0 0 1-1-1Z" clip-rule="evenodd"/></svg>
                    </button>
                </form>

                <form method="POST" action="{{ route('payments.callback', $payment->reference) }}">
                    @csrf
                    <input type="hidden" name="outcome" value="fail">
                    <input type="hidden" name="amount_cents" value="{{ $fields['fail']['amount_cents'] }}">
                    <input type="hidden" name="expires" value="{{ $fields['fail']['expires'] }}">
                    <input type="hidden" name="signature" value="{{ $fields['fail']['signature'] }}">
                    <button type="submit" class="flex w-full items-center gap-3 rounded-none border border-red-200 bg-red-50 p-4 text-start transition hover:border-red-400 hover:bg-red-100">
                        <span class="flex-1">
                            <span class="block font-bold text-red-900">{{ __('payments.fail') }}</span>
                            <span class="mt-0.5 block text-xs text-red-800">{{ __('payments.fail_desc') }}</span>
                        </span>
                    </button>
                </form>

                <form method="POST" action="{{ route('payments.callback', $payment->reference) }}">
                    @csrf
                    <input type="hidden" name="outcome" value="cancel">
                    <input type="hidden" name="amount_cents" value="{{ $fields['cancel']['amount_cents'] }}">
                    <input type="hidden" name="expires" value="{{ $fields['cancel']['expires'] }}">
                    <input type="hidden" name="signature" value="{{ $fields['cancel']['signature'] }}">
                    <button type="submit" class="flex w-full items-center gap-3 rounded-none border border-slate-200 bg-slate-50 p-4 text-start transition hover:border-slate-400 hover:bg-slate-100">
                        <span class="flex-1">
                            <span class="block font-bold text-slate-800">{{ __('payments.cancel') }}</span>
                            <span class="mt-0.5 block text-xs text-slate-600">{{ __('payments.cancel_desc') }}</span>
                        </span>
                    </button>
                </form>

                <p class="rounded-none bg-slate-100 p-3 text-[11px] leading-relaxed text-slate-600">{{ __('payments.signed_note') }}</p>

                @php
                    $backUrl = auth()->check() && (auth()->id() === $order->user_id || auth()->user()->canAccessAdmin())
                        ? route('orders.show', $order->number)
                        : \Illuminate\Support\Facades\URL::temporarySignedRoute('orders.show', now()->addDays(7), ['order' => $order->number]);
                @endphp
                <a href="{{ $backUrl }}" class="block text-center text-sm font-semibold text-slate-500 hover:text-brand-800">{{ __('payments.back_to_order') }}</a>
            </div>
        </div>
    </div>
@endsection
