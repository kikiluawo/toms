@props(['size' => 'md'])

@php
    $uid = uniqid('turbion-logo-');
    $sizes = [
        'sm' => 'h-10 w-10',
        'md' => 'h-11 w-11',
        'lg' => 'h-14 w-14',
    ];
@endphp

<div
    {{ $attributes->merge([
        'class' => 'relative flex shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-petrol-800 via-petrol-900 to-[#04100F] shadow-lg shadow-petrol-900/30 ring-1 ring-inset ring-white/15 ' . ($sizes[$size] ?? $sizes['md']),
    ]) }}
    role="img"
    aria-label="TURBION oil drop logo"
>
    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-[68%] w-[68%]" aria-hidden="true">
        <defs>
            <clipPath id="{{ $uid }}-clip">
                <path d="M12 2.4c3.9 4.4 7 8 7 11.7a7 7 0 1 1-14 0C5 10.4 8.1 6.8 12 2.4Z" />
            </clipPath>
            <linearGradient id="{{ $uid }}-oil" x1="0" y1="10" x2="0" y2="24" gradientUnits="userSpaceOnUse">
                <stop stop-color="#FFE29A" />
                <stop offset="0.55" stop-color="#E3A93A" />
                <stop offset="1" stop-color="#9C5B08" />
            </linearGradient>
        </defs>

        <path
            d="M12 2.4c3.9 4.4 7 8 7 11.7a7 7 0 1 1-14 0C5 10.4 8.1 6.8 12 2.4Z"
            fill="#FFFFFF"
            fill-opacity=".14"
            stroke="#FFFFFF"
            stroke-opacity=".85"
            stroke-width="1.1"
        />

        <g clip-path="url(#{{ $uid }}-clip)">
            <g class="turbion-wave">
                <path
                    d="M-24 13q3 -2 6 0t6 0t6 0t6 0t6 0t6 0t6 0t6 0t6 0t6 0t6 0t6 0V24H-24Z"
                    fill="url(#{{ $uid }}-oil)"
                />
            </g>
            <g class="turbion-wave-slow">
                <path
                    d="M-24 14.6q3 -2 6 0t6 0t6 0t6 0t6 0t6 0t6 0t6 0t6 0t6 0t6 0t6 0V24H-24Z"
                    fill="#FFD37A"
                    fill-opacity=".5"
                />
            </g>
            <circle class="turbion-bubble" cx="9.6" cy="19.5" r="1" fill="#FFF0C2" />
            <circle class="turbion-bubble-late" cx="14.2" cy="20.5" r="0.75" fill="#FFF0C2" />
        </g>

        <circle cx="9.4" cy="8.7" r="0.9" fill="#FFFFFF" fill-opacity=".5" />
    </svg>
</div>
