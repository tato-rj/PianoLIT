<svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($kind)
        @case('t-shirts')
            <path d="m23 12-9 4-8 13 10 6 4-6v24h24V29l4 6 10-6-8-13-9-4" fill="var(--pastel-peach-accent)" />
            <path d="M23 12c0 6 3 9 9 9s9-3 9-9l-6 2h-6z" fill="var(--color-surface)" />
            <path d="M23 49h18M35 30v11m0-11 6 2" />
            <ellipse cx="31.5" cy="42" rx="3.5" ry="2.5" fill="currentColor" stroke="none" />
            @break
        @case('stickers')
            <path d="M15 10h34a5 5 0 0 1 5 5v22L37 54H15a5 5 0 0 1-5-5V15a5 5 0 0 1 5-5Z" fill="var(--color-surface)" />
            <path d="M54 37H42a5 5 0 0 0-5 5v12" fill="var(--pastel-sky-accent)" />
            <path d="M27 34V22l13-3v12M27 26l13-3" />
            <ellipse cx="23.5" cy="35" rx="3.5" ry="2.5" fill="currentColor" stroke="none" />
            <ellipse cx="36.5" cy="32" rx="3.5" ry="2.5" fill="currentColor" stroke="none" />
            @break
        @case('ebooks')
            <path d="M8 13h13c5 0 8 2 11 5 3-3 6-5 11-5h13v39H43c-5 0-8 1-11 4-3-3-6-4-11-4H8Z" fill="var(--pastel-rose-accent)" />
            <path d="M12 9h10c4 0 8 2 10 5 2-3 6-5 10-5h10v38H42c-4 0-8 2-10 5-2-3-6-5-10-5H12Z" fill="var(--color-surface)" />
            <path d="M32 14v38M18 22h8m-8 7h8m-8 7h8m12-14h8m-8 7h8m-8 7h8" />
            @break
        @case('escores')
            <path d="M15 8h25l10 10v38H15Z" fill="var(--color-surface)" />
            <path d="M40 8v10h10" fill="var(--pastel-cream-accent)" />
            <path d="M21 28h23m-23 5h23m-23 5h23m-23 5h23m-23 5h23" stroke-opacity=".35" stroke-width="1.5" />
            <path d="M29 40V25l12-3v15M29 29l12-3" />
            <ellipse cx="25.5" cy="41" rx="3.5" ry="2.5" fill="currentColor" stroke="none" />
            <ellipse cx="37.5" cy="38" rx="3.5" ry="2.5" fill="currentColor" stroke="none" />
            @break
        @default
            <circle cx="21" cy="21" r="7" />
            <circle cx="43" cy="21" r="7" />
            <circle cx="21" cy="43" r="7" />
            <circle cx="43" cy="43" r="7" />
    @endswitch
</svg>
