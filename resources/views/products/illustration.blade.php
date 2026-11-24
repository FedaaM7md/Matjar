{{--
    Original, per-category garment illustration. The garment is filled with the
    product's variant colour (so a grid of cards varies), outlined in ink or
    white depending on luminance. Used inline and rendered to SVG files by the
    demo seeder. No photography, no third-party art.
--}}
@php
    $slug = (string) ($category ?? '');
    $shape = $shape ?? match (true) {
        str_contains($slug, 'tshirt') => 'tee',
        str_contains($slug, 'shirt') => 'shirt',
        str_contains($slug, 'pant') => 'pants',
        str_contains($slug, 'hoodie') || str_contains($slug, 'sweat') => 'hoodie',
        str_contains($slug, 'bag') => 'tote',
        str_contains($slug, 'accessor') => 'cap',
        default => 'tee',
    };

    $rawColor = strtoupper((string) ($color ?? '#D8C3A5'));
    $hex = preg_match('/^#[0-9A-F]{6}$/', $rawColor) ? $rawColor : '#D8C3A5';
    [$r, $g, $b] = [hexdec(substr($hex, 1, 2)), hexdec(substr($hex, 3, 2)), hexdec(substr($hex, 5, 2))];
    $luminance = 0.299 * $r + 0.587 * $g + 0.114 * $b;
    $stroke = $luminance > 150 ? '#111111' : '#F4F1EC';
@endphp
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 400" class="{{ $class ?? '' }}"
     @if (! empty($label)) role="img" aria-label="{{ $label }}" @else aria-hidden="true" @endif>
    <rect width="400" height="400" fill="#EDEAE6"/>
    <circle cx="322" cy="78" r="118" fill="#E63B2E" fill-opacity="0.08"/>
    <circle cx="70" cy="336" r="96" fill="#111111" fill-opacity="0.05"/>

    <g fill="{{ $hex }}" stroke="{{ $stroke }}" stroke-width="10" stroke-linejoin="round" stroke-linecap="round">
        @switch($shape)
            @case('shirt')
                <path d="M150 122 L200 140 L250 122 L296 146 L282 198 L258 186 L258 312 L142 312 L142 186 L118 198 L104 146 Z"/>
                @break
            @case('pants')
                <path d="M152 108 H248 L248 312 H206 L200 188 L194 312 H152 Z"/>
                @break
            @case('hoodie')
                <path d="M150 152 L200 134 L250 152 L292 174 L280 222 L256 210 L256 312 H144 V210 L120 222 L108 174 Z"/>
                @break
            @case('tote')
                <path d="M132 158 H268 L282 306 Q283 316 272 316 H128 Q117 316 118 306 Z"/>
                @break
            @case('cap')
                <path d="M124 244 a76 64 0 0 1 152 0 Z"/>
                <path d="M112 244 q88 48 176 0 l10 16 q-98 54 -196 0 Z"/>
                @break
            @default
                <path d="M158 106 L200 126 L242 106 L300 138 L282 190 L256 178 L256 300 Q256 312 244 312 L156 312 Q144 312 144 300 L144 178 L118 190 L100 138 Z"/>
        @endswitch
    </g>

    {{-- Garment details (outline only). --}}
    <g fill="none" stroke="{{ $stroke }}" stroke-width="6" stroke-linecap="round" stroke-linejoin="round">
        @switch($shape)
            @case('shirt')
                <path d="M178 122 L200 154 L222 122"/>
                <path d="M200 154 V312"/>
                <path d="M150 214 H150"/>
                @break
            @case('pants')
                <path d="M152 142 H248"/>
                <path d="M200 142 V188"/>
                @break
            @case('hoodie')
                <path d="M168 152 Q200 116 232 152 Q216 170 200 170 Q184 170 168 152 Z"/>
                <path d="M176 244 H224 V290 H176 Z"/>
                <path d="M186 170 v18 M214 170 v18"/>
                @break
            @case('tote')
                <path d="M168 158 V140 a32 32 0 0 1 64 0 V158"/>
                @break
            @case('cap')
                <path d="M200 182 v10"/>
                @break
            @default
                <path d="M178 118 Q200 142 222 118"/>
        @endswitch
    </g>

    {{-- Single tomato accent. --}}
    @switch($shape)
        @case('pants')
            <rect x="150" y="160" width="20" height="16" fill="#E63B2E"/>
            @break
        @case('cap')
            <rect x="118" y="250" width="18" height="12" fill="#E63B2E"/>
            @break
        @case('hoodie')
            <rect x="232" y="252" width="16" height="16" fill="#E63B2E"/>
            @break
        @case('tote')
            <rect x="190" y="230" width="20" height="18" fill="#E63B2E"/>
            @break
        @case('shirt')
            <rect x="246" y="196" width="16" height="16" fill="#E63B2E"/>
            @break
        @default
            <rect x="186" y="270" width="28" height="18" fill="#E63B2E"/>
    @endswitch
</svg>
