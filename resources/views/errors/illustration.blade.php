{{-- A curious cat and a few misplaced pages. Decorative, silent, and finite motion. --}}
<svg class="error-page__illustration" viewBox="0 0 360 240" fill="none" aria-hidden="true" focusable="false">
    <path d="M59 167c-18-48 2-104 49-123 38-15 60 3 88 0 46-6 86 13 101 54 20 55-4 97-53 105-51 8-166 14-185-36Z" fill="#eff4ff"/>
    <circle cx="284" cy="66" r="9" fill="#ffe8ac"/>
    <circle cx="65" cy="122" r="5" fill="#c3d4fb"/>
    <path d="m81 72 4 8m-8-2 12-4" stroke="#b6c9ef" stroke-width="3" stroke-linecap="round"/>
    <ellipse cx="183" cy="211" rx="120" ry="8" fill="#e8edf6"/>
    <g class="error-cat__tail">
        <path d="M161 195c-43 9-76-7-79-33-2-13 4-26 14-24 9 2 0 14 4 23 6 15 30 13 45 7" stroke="#303a59" stroke-width="17" stroke-linecap="round"/>
    </g>
    <path d="M123 188c0-40 10-65 47-65 31 0 57 23 57 56v20H136c-9 0-13-3-13-11Z" fill="#303a59"/>
    <path d="M154 176c0-20 9-34 24-34s26 16 26 35v22h-50v-23Z" fill="#46516f"/>
    <g class="error-cat__head">
        <path d="m141 107-3-45 30 18c10-3 22-3 32 0l28-19-2 47c8 34-15 52-44 52-29 0-51-19-41-53Z" fill="#303a59"/>
        <path d="m146 77 2 20 13-9-15-11Zm73-1-13 12 13 9V76Z" fill="#f1bcac"/>
        @if($scene === 'rest')
            <path d="M157 119q7 8 14 0m23 0q7 8 14 0" stroke="#fff" stroke-width="3" stroke-linecap="round"/>
        @else
            <g class="error-cat__eyes">
                <ellipse cx="165" cy="117" rx="8" ry="10" fill="#fff"/>
                <ellipse cx="201" cy="117" rx="8" ry="10" fill="#fff"/>
                <ellipse cx="168" cy="118" rx="3.5" ry="5.5" fill="#172033"/>
                <ellipse cx="204" cy="118" rx="3.5" ry="5.5" fill="#172033"/>
            </g>
        @endif
        <path d="m179 131 4 4 4-4" fill="#f1bcac"/>
        <path d="M183 136v3m0 0q-5 6-9 0m9 0q5 6 9 0" stroke="#d9e0ef" stroke-width="1.8" stroke-linecap="round"/>
        <path d="m145 130-17-3m17 9-17 3m94-9 16-3m-16 9 16 3" stroke="#7c87a3" stroke-width="1.8" stroke-linecap="round"/>
    </g>
    @if($scene === 'stack')
        <g transform="rotate(-8 247 176)">
            <rect x="210" y="180" width="80" height="22" rx="4" fill="#b7cafa" stroke="#7e9edc" stroke-width="2"/>
            <path d="M220 185h69v12h-69" fill="white"/>
            <rect x="202" y="156" width="78" height="23" rx="4" fill="#f5d890" stroke="#d9b971" stroke-width="2"/>
            <path d="M211 161h68v13h-68" fill="#fffaf0"/>
            <rect x="216" y="117" width="65" height="38" rx="4" fill="#d4c7ef" stroke="#b2a3d0" stroke-width="2"/>
            <path d="M225 123h55v25h-55" fill="#fff"/>
            <path d="M231 130h41m-41 8h41m-55 52h55m-53-22h52" stroke="#d3d9e3" stroke-width="2"/>
        </g>
        <path d="m203 173 21 4" stroke="#303a59" stroke-width="13" stroke-linecap="round"/>
    @elseif($scene === 'rest')
        <rect x="216" y="168" width="49" height="36" rx="12" fill="#f7d894"/>
        <path d="M265 177h5a9 9 0 0 1 0 18h-6" stroke="#e0bf76" stroke-width="5"/>
        <path class="error-cat__paper" d="M232 155c-9-10 8-13 0-23m13 23c-9-10 8-13 0-23" stroke="#c6d4eb" stroke-width="3" stroke-linecap="round"/>
        <path d="M141 195h66" stroke="#303a59" stroke-width="17" stroke-linecap="round"/>
    @else
        <g class="error-cat__paper">
            <path d="m236 70 44 12-23 80-57-16 19-66 17-10Z" fill="#fff" stroke="#a8bde5" stroke-width="2" stroke-linejoin="round"/>
            <path d="m236 70-4 16-13-6m9 19 34 9m-38 3 33 9m-36 3 33 9m-36 3 23 6" stroke="#c7d5ee" stroke-width="2" stroke-linecap="round"/>
        </g>
        @if($scene === 'peek')
            <g transform="rotate(-8 180 193)">
                <path d="M119 173q32-10 63 0 29-10 59 0v37q-31-7-59 0-32-7-63 0v-37Z" fill="#fff" stroke="#97aed7" stroke-width="2" stroke-linejoin="round"/>
                <path d="M182 174v35m-51-25h38m-38 8h33m31-8h34m-34 8h38" stroke="#c7d5ee" stroke-width="2" stroke-linecap="round"/>
            </g>
            <path d="M137 171v8m91-8v8" stroke="#303a59" stroke-width="12" stroke-linecap="round"/>
        @else
            <rect x="119" y="184" width="144" height="26" rx="5" fill="#fff" stroke="#9cafcf" stroke-width="2"/>
            <path d="M137 185v24m18-24v24m18-24v24m18-24v24m18-24v24m18-24v24m18-24v24" stroke="#c6d1e3" stroke-width="2"/>
            <path d="M137 185v11m18-11v11m36-11v11m18-11v11m18-11v11" stroke="#303a59" stroke-width="8"/>
            <path d="m151 172 15 16m37-16 13 16" stroke="#303a59" stroke-width="13" stroke-linecap="round"/>
        @endif
    @endif
</svg>
