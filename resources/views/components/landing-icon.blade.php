@props(['name'])

<svg
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.8"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    {{ $attributes }}
>
    @switch($name)
        @case('globe')
            <circle cx="12" cy="12" r="9" />
            <path d="M3 12h18M12 3c2.4 2.5 3.6 5.5 3.6 9S14.4 18.5 12 21M12 3C9.6 5.5 8.4 8.5 8.4 12S9.6 18.5 12 21" />
            @break

        @case('shield')
        @case('shield-check')
            <path d="M12 3 19 6v5c0 4.7-2.9 8-7 10-4.1-2-7-5.3-7-10V6l7-3Z" />
            @if ($name === 'shield-check')
                <path d="m9 12 2 2 4-5" />
            @endif
            @break

        @case('layers')
            <path d="m12 3 9 5-9 5-9-5 9-5Z" />
            <path d="m3 12 9 5 9-5M3 16l9 5 9-5" />
            @break

        @case('code')
            <path d="m8.5 8-4 4 4 4M15.5 8l4 4-4 4M14 5l-4 14" />
            @break

        @case('server')
            <rect x="4" y="3" width="16" height="6" rx="2" />
            <rect x="4" y="15" width="16" height="6" rx="2" />
            <path d="M8 6h.01M8 18h.01M12 9v6" />
            @break

        @case('expand')
            <path d="M8 3H3v5M16 3h5v5M8 21H3v-5M16 21h5v-5M3 8l6-6M21 8l-6-6M3 16l6 6M21 16l-6 6" />
            @break

        @case('pulse')
            <path d="M3 12h4l2.2-6 4 12 2.2-6H21" />
            @break

        @case('bot')
            <rect x="4" y="7" width="16" height="13" rx="3" />
            <path d="M12 3v4M9 3h6M8 12h.01M16 12h.01M8 16c1.2.8 2.5 1.2 4 1.2s2.8-.4 4-1.2" />
            @break

        @case('support')
            <circle cx="12" cy="12" r="9" />
            <path d="M8.5 10a3.5 3.5 0 0 1 7 0c0 2.5-3.5 2.5-3.5 5M12 18h.01" />
            @break

        @case('check-circle')
            <circle cx="12" cy="12" r="9" />
            <path d="m8 12 2.5 2.5L16 9" />
            @break

        @case('arrow-right')
            <path d="M5 12h14M14 7l5 5-5 5" />
            @break

        @case('chevron-down')
            <path d="m6 9 6 6 6-6" />
            @break

        @case('menu')
            <path d="M4 7h16M4 12h16M4 17h16" />
            @break

        @case('close')
            <path d="m6 6 12 12M18 6 6 18" />
            @break

        @case('network')
            <circle cx="12" cy="5" r="2.5" />
            <circle cx="6" cy="18" r="2.5" />
            <circle cx="18" cy="18" r="2.5" />
            <path d="m10.9 7.3-3.8 8.4M13.1 7.3l3.8 8.4M8.5 18h7" />
            @break

        @default
            <circle cx="12" cy="12" r="9" />
            <path d="M12 8v4M12 16h.01" />
    @endswitch
</svg>
