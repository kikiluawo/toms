@props(['class' => ''])

@php
    $uid = uniqid('oil-scene-');
@endphp

<svg
    {{ $attributes->merge(['class' => 'pump-scene ' . $class]) }}
    viewBox="0 0 520 380"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    aria-hidden="true"
>
    <defs>
        <linearGradient id="{{ $uid }}-steel" x1="80" y1="120" x2="440" y2="340" gradientUnits="userSpaceOnUse">
            <stop stop-color="#F6D48F" stop-opacity=".7" />
            <stop offset="1" stop-color="#C98B1E" stop-opacity=".22" />
        </linearGradient>
        <linearGradient id="{{ $uid }}-oil" x1="0" y1="296" x2="0" y2="356" gradientUnits="userSpaceOnUse">
            <stop stop-color="#FFD37A" />
            <stop offset="1" stop-color="#A96D14" />
        </linearGradient>
        <radialGradient id="{{ $uid }}-sun" cx="0.5" cy="0.5" r="0.5">
            <stop stop-color="#E3BF64" stop-opacity=".38" />
            <stop offset="1" stop-color="#E3BF64" stop-opacity="0" />
        </radialGradient>
    </defs>

    <circle class="pump-sun" cx="392" cy="96" r="86" fill="url(#{{ $uid }}-sun)" />

    <path d="M16 340h488" stroke="#FFFFFF" stroke-opacity=".12" stroke-width="3" stroke-linecap="round" />
    <ellipse class="pump-puddle" cx="160" cy="343" rx="122" ry="11" fill="url(#{{ $uid }}-oil)" opacity=".5" />

    <g stroke="url(#{{ $uid }}-steel)" stroke-width="6" stroke-linecap="round" stroke-linejoin="round">
        <path d="M150 340h230" />
        <path d="M185 340 245 170" />
        <path d="M305 340 245 170" />
        <path d="M212 258h66" />
        <path d="M330 340h70" stroke-opacity=".55" />
    </g>

    <g class="pump-crank">
        <circle cx="362" cy="300" r="34" stroke="url(#{{ $uid }}-steel)" stroke-width="5" />
        <path d="M362 300v-32M362 300l28 17M362 300l-28 17" stroke="url(#{{ $uid }}-steel)" stroke-width="4" stroke-linecap="round" />
        <circle cx="362" cy="268" r="6" fill="#E3A93A" />
    </g>

    <g class="pump-beam" stroke="url(#{{ $uid }}-steel)" stroke-linecap="round" stroke-linejoin="round">
        <path d="M96 201 394 146" stroke-width="6" />
        <path d="M394 146l40 60" stroke-width="6" />
        <circle cx="440" cy="212" r="16" stroke-width="5" fill="#C98B1E" fill-opacity=".14" />
        <path d="M96 201c-16-10-18-29-4-40 10 12 12 26 4 40Z" stroke-width="5" fill="#C98B1E" fill-opacity=".2" />
        <circle cx="245" cy="170" r="8" stroke-width="5" fill="#0A2322" />
    </g>

    <g class="pump-rod" stroke="url(#{{ $uid }}-steel)" stroke-width="5" stroke-linecap="round">
        <path d="M92 216v54" />
        <path d="M76 274h32" />
    </g>

    <circle class="pump-drop" cx="92" cy="292" r="5" fill="url(#{{ $uid }}-oil)" />
    <circle class="pump-drop pump-drop-2" cx="92" cy="292" r="3.6" fill="url(#{{ $uid }}-oil)" />
</svg>
