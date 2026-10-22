<svg xmlns="http://www.w3.org/2000/svg" width="280" height="140" viewBox="0 0 280 140" fill="none" aria-hidden="true" focusable="false">
    @switch($kind)
        @case('circle')
            <circle cx="140" cy="70" r="65" fill="var(--color-surface)"/>
            <circle cx="140" cy="70" r="48" stroke="currentColor" stroke-opacity=".13" stroke-width="22"/>
            <circle cx="140" cy="70" r="48" stroke="currentColor" stroke-opacity=".45" stroke-width="22" stroke-dasharray="23 279" transform="rotate(-103 140 70)"/>
            @foreach(['C', 'G', 'D', 'A', 'E', 'B', 'F♯', 'D♭', 'A♭', 'E♭', 'B♭', 'F'] as $index => $note)
                @php($angle = deg2rad($index * 30 - 90))
                <text x="{{ 140 + 49 * cos($angle) }}" y="{{ 73 + 49 * sin($angle) }}" fill="currentColor" text-anchor="middle" font-family="var(--font-family-body)" font-size="10" font-weight="600">{{ $note }}</text>
            @endforeach
            <circle cx="140" cy="70" r="27" fill="currentColor" fill-opacity=".07"/>
            <path d="M140 80V55L151 52V75" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
            <ellipse cx="135" cy="81" rx="6" ry="4" fill="currentColor"/>
            <ellipse cx="146" cy="76" rx="6" ry="4" fill="currentColor"/>
            @break
        @case('chords')
            <rect x="97" y="7" width="86" height="28" rx="14" fill="var(--color-surface)"/>
            <text x="140" y="25" text-anchor="middle" fill="currentColor" font-family="var(--font-family-body)" font-size="11" font-weight="600">C major</text>
            <rect x="24" y="48" width="232" height="78" rx="8" fill="var(--color-surface)"/>
            @foreach([24, 90, 156] as $keyX)
                <rect x="{{ $keyX + 3 }}" y="51" width="28" height="72" rx="4" fill="currentColor" fill-opacity=".16"/>
                <circle cx="{{ $keyX + 17 }}" cy="109" r="4" fill="currentColor"/>
            @endforeach
            @foreach([57, 90, 123, 156, 189, 222] as $keyX)
                <path d="M{{ $keyX }} 49V125" stroke="currentColor" stroke-opacity=".15"/>
            @endforeach
            @foreach([49, 82, 148, 181, 214] as $keyX)
                <rect x="{{ $keyX }}" y="48" width="16" height="43" rx="3" fill="var(--card-ink)"/>
            @endforeach
            @break
        @case('courses')
            <rect x="76" y="9" width="139" height="99" rx="10" fill="currentColor" fill-opacity=".13" transform="rotate(7 145 60)"/>
            <rect x="61" y="10" width="151" height="111" rx="10" fill="var(--color-surface)" transform="rotate(-4 136 65)"/>
            <circle cx="88" cy="37" r="12" fill="currentColor" fill-opacity=".13"/>
            <text x="88" y="41" text-anchor="middle" fill="currentColor" font-family="var(--font-family-body)" font-size="11" font-weight="600">1</text>
            <path d="M110 33H184M110 41H161M78 61H186M78 72H169" stroke="currentColor" stroke-opacity=".3" stroke-width="4" stroke-linecap="round"/>
            <rect x="43" y="83" width="194" height="49" rx="10" fill="var(--card-accent)"/>
            <path d="M48 88L132 119C137 121 143 121 148 119L232 88" stroke="currentColor" stroke-opacity=".55" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            @break
        @case('infographics')
            <rect x="69" y="13" width="110" height="117" rx="8" fill="currentColor" fill-opacity=".13" transform="rotate(-9 124 71)"/>
            <rect x="96" y="8" width="110" height="124" rx="8" fill="var(--color-surface)" transform="rotate(5 151 70)"/>
            <g transform="rotate(5 151 70)">
                <rect x="108" y="21" width="60" height="7" rx="3.5" fill="currentColor" fill-opacity=".6"/>
                <path d="M108 38H190" stroke="currentColor" stroke-opacity=".15" stroke-width="3" stroke-linecap="round"/>
                @foreach([0, 1, 2, 3, 4] as $line)
                    <path d="M108 {{ 52 + $line * 5 }}H190" stroke="currentColor" stroke-opacity=".17"/>
                @endforeach
                <ellipse cx="132" cy="66" rx="6" ry="4" fill="currentColor"/>
                <path d="M138 65V47" stroke="currentColor" stroke-width="2"/>
                <ellipse cx="170" cy="61" rx="6" ry="4" fill="currentColor"/>
                <path d="M176 60V42" stroke="currentColor" stroke-width="2"/>
                <rect x="108" y="88" width="23" height="27" rx="5" fill="currentColor" fill-opacity=".12"/>
                <rect x="138" y="88" width="23" height="27" rx="5" fill="currentColor" fill-opacity=".24"/>
                <rect x="168" y="88" width="23" height="27" rx="5" fill="currentColor" fill-opacity=".4"/>
            </g>
            @break
        @case('quizzes')
            <rect x="66" y="12" width="148" height="116" rx="14" fill="var(--color-surface)"/>
            <rect x="82" y="27" width="116" height="5" rx="2.5" fill="currentColor" fill-opacity=".1"/>
            <rect x="82" y="27" width="67" height="5" rx="2.5" fill="currentColor" fill-opacity=".5"/>
            <text x="140" y="82" text-anchor="middle" fill="currentColor" font-family="var(--font-family-heading)" font-size="43" font-weight="600">?</text>
            <rect x="82" y="94" width="54" height="21" rx="8" fill="currentColor" fill-opacity=".13"/>
            <path d="M102 104L107 109L116 100" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <rect x="144" y="94" width="54" height="21" rx="8" fill="currentColor" fill-opacity=".07"/>
            <path d="M167 101L174 108M174 101L167 108" stroke="currentColor" stroke-opacity=".4" stroke-width="2" stroke-linecap="round"/>
            @break
        @case('pianists')
            <g transform="rotate(-8 95 75)">
                <rect x="44" y="22" width="95" height="105" rx="10" fill="var(--color-surface)"/>
                <rect x="53" y="31" width="77" height="70" rx="6" fill="currentColor" fill-opacity=".07"/>
                <path d="M68 99C68 79 115 79 115 99" fill="currentColor" fill-opacity=".65"/>
                <ellipse cx="91" cy="62" rx="15" ry="19" fill="currentColor" fill-opacity=".22"/>
                <path d="M76 64C65 38 113 31 107 65L100 50L78 58Z" fill="currentColor" fill-opacity=".65"/>
                <path d="M66 112H117" stroke="currentColor" stroke-opacity=".25" stroke-width="4" stroke-linecap="round"/>
            </g>
            <g transform="rotate(6 185 67)">
                <rect x="143" y="9" width="95" height="116" rx="10" fill="var(--color-surface)"/>
                <rect x="152" y="18" width="77" height="82" rx="6" fill="currentColor" fill-opacity=".12"/>
                <path d="M166 97C166 76 216 76 216 97" fill="currentColor" fill-opacity=".65"/>
                <path d="M171 66C161 24 219 26 211 73L203 90H177Z" fill="currentColor" fill-opacity=".6"/>
                <ellipse cx="191" cy="55" rx="14" ry="18" fill="var(--card-surface)"/>
                <path d="M176 52L196 39L207 58C211 32 176 26 176 52Z" fill="currentColor" fill-opacity=".65"/>
                <path d="M165 112H216" stroke="currentColor" stroke-opacity=".25" stroke-width="4" stroke-linecap="round"/>
            </g>
            @break
    @endswitch
</svg>
