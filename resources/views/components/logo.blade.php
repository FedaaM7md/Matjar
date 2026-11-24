@props(['class' => 'h-10 w-auto'])

{{--
    Matjar logo mark: a shopping-bag outline whose two handles rise as the
    peaks of an "M", with a centre crease completing the letter. The body is
    drawn in currentColor so it adapts to light and dark surfaces; the
    handles carry the single tomato-red accent.
--}}
<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="{{ __('common.logo_alt') }}">
    <path d="M14 21.5h20l-1.9 14.9a3 3 0 0 1-2.98 2.6H18.9a3 3 0 0 1-2.98-2.6L14 21.5Z"
          stroke="currentColor" stroke-width="2.6" stroke-linejoin="round"/>
    <path d="M18 22.1v-2.6a3.2 3.2 0 0 1 6.4 0" stroke="#E63B2E" stroke-width="2.6" stroke-linecap="round"/>
    <path d="M23.6 22.1v-2.6a3.2 3.2 0 0 1 6.4 0" stroke="#E63B2E" stroke-width="2.6" stroke-linecap="round"/>
    <path d="M24 22.1v5.4" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/>
</svg>
