@if (session('status') || session('error') || $errors->any())
    <div class="container-page pt-4" aria-live="polite">
        @if (session('status'))
            <div x-data="dismissible" x-show="visible" x-transition.opacity
                 class="flex items-start gap-3 rounded-none border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.7-9.3a1 1 0 0 0-1.4-1.4L9 10.6 7.7 9.3a1 1 0 0 0-1.4 1.4l2 2a1 1 0 0 0 1.4 0l4-4Z" clip-rule="evenodd"/>
                </svg>
                <p class="flex-1 font-semibold">{{ session('status') }}</p>
                <button type="button" @click="dismiss()" class="text-emerald-700 hover:text-emerald-900" aria-label="{{ __('common.close') }}">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.3 5 5 6.3 8.7 10 5 13.7 6.3 15 10 11.3 13.7 15 15 13.7 11.3 10 15 6.3 13.7 5 10 8.7 6.3 5Z"/></svg>
                </button>
            </div>
        @endif

        @if (session('error'))
            <div x-data="dismissible" data-delay="9000" x-show="visible" x-transition.opacity
                 class="mt-3 flex items-start gap-3 rounded-none border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900" role="alert">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-red-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm1-5a1 1 0 1 1-2 0V7a1 1 0 1 1 2 0v6Zm-1 3.5a1.1 1.1 0 1 0 0-2.2 1.1 1.1 0 0 0 0 2.2Z" clip-rule="evenodd"/>
                </svg>
                <p class="flex-1 font-semibold">{{ session('error') }}</p>
                <button type="button" @click="dismiss()" class="text-red-700 hover:text-red-900" aria-label="{{ __('common.close') }}">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.3 5 5 6.3 8.7 10 5 13.7 6.3 15 10 11.3 13.7 15 15 13.7 11.3 10 15 6.3 13.7 5 10 8.7 6.3 5Z"/></svg>
                </button>
            </div>
        @endif

        @if ($errors->any())
            <div class="mt-3 rounded-none border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900" role="alert">
                <p class="font-bold">{{ __('common.check_fields') }}</p>
                <ul class="mt-1 list-inside list-disc space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endif
