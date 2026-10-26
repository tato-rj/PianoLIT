{{-- Editable examples, not interactive controls. Each card names its destination. --}}
<svg xmlns="http://www.w3.org/2000/svg" width="480" height="240" viewBox="0 0 480 240" fill="none" aria-hidden="true" focusable="false" font-family="var(--font-family-body)">
    @switch($kind)
        @case('circle')
            <path fill="var(--color-primary)" d="M0 0H480V240H0z"/>
            @foreach(range(0, 480, 30) as $line)
                <path d="M{{ $line }} 0V240" stroke="white" stroke-opacity=".07"/>
            @endforeach
            @foreach(range(0, 240, 30) as $line)
                <path d="M0 {{ $line }}H480" stroke="white" stroke-opacity=".07"/>
            @endforeach
            <g fill="white">
                <text x="28" y="44" font-size="12" letter-spacing="2">FIND YOUR KEY</text>
                <text x="26" y="106" font-size="35" font-weight="600">It all</text>
                <text x="26" y="146" font-size="35" font-weight="600">connects</text>
                <path d="M28 187H112M105 180L112 187L105 194" stroke="white" stroke-width="2"/>
            </g>
            <circle cx="336" cy="120" r="102" fill="white"/>
            <circle cx="336" cy="120" r="76" stroke="#e8ecf3" stroke-width="35"/>
            <circle cx="336" cy="120" r="76" stroke="#ffd338" stroke-width="35" stroke-dasharray="37 441" transform="rotate(-104 336 120)"/>
            @foreach(['C', 'G', 'D', 'A', 'E', 'B', 'F♯', 'D♭', 'A♭', 'E♭', 'B♭', 'F'] as $index => $note)
                @php($angle = deg2rad($index * 30 - 90))
                <text x="{{ 336 + 76 * cos($angle) }}" y="{{ 125 + 76 * sin($angle) }}" fill="#20242b" text-anchor="middle" font-size="16" font-weight="600">{{ $note }}</text>
            @endforeach
            <text x="336" y="122" fill="#20242b" text-anchor="middle" font-size="29" font-weight="600">C</text>
            <text x="336" y="143" fill="#5b6575" text-anchor="middle" font-size="12">MAJOR</text>
            @break
        @case('chords')
            <path fill="#20242b" d="M0 0H480V240H0z"/>
            <text x="30" y="36" fill="#bcc3ce" font-size="12" letter-spacing="2">THREE NOTES. ONE CHORD</text>
            <text x="30" y="84" fill="white" font-size="35" font-weight="600">C + E + G</text>
            <path d="M240 73H276M269 66L276 73L269 80" stroke="#ffd338" stroke-width="2"/>
            <text x="300" y="84" fill="#ffd338" font-size="29" font-weight="600">C major</text>
            <rect x="30" y="112" width="420" height="140" rx="6" fill="white"/>
            @foreach([30, 150, 270] as $keyX)
                <path d="M{{ $keyX + 2 }} 114H{{ $keyX + 58 }}V240H{{ $keyX + 2 }}z" fill="#ffd338"/>
            @endforeach
            @foreach([90, 150, 210, 270, 330, 390] as $keyX)
                <path d="M{{ $keyX }} 113V240" stroke="#c5cad2"/>
            @endforeach
            @foreach([74, 134, 254, 314, 374] as $keyX)
                <rect x="{{ $keyX }}" y="112" width="32" height="78" rx="3" fill="#20242b"/>
                <path d="M{{ $keyX + 5 }} 120V181H{{ $keyX + 27 }}" stroke="white" stroke-opacity=".12"/>
            @endforeach
            @foreach(['C' => 60, 'E' => 180, 'G' => 300] as $note => $keyX)
                <text x="{{ $keyX }}" y="222" fill="#20242b" text-anchor="middle" font-size="19" font-weight="600">{{ $note }}</text>
            @endforeach
            @break
        @case('courses')
            <path fill="#e85632" d="M0 0H480V240H0z"/>
            <path d="M0 195L125 72L273 240H0M373 0L480 91V0" fill="#c53a1a"/>
            <g transform="rotate(-5 246 130)">
                <path d="M114 39H399V260H114z" fill="#a53118" fill-opacity=".4"/>
                <path d="M100 24H385V260H100z" fill="#fffdf7"/>
                <text x="124" y="55" fill="#c53a1a" font-size="11" letter-spacing="1.8">PIANOLIT / CRASH COURSES</text>
                <path d="M124 70H361" stroke="#d7d2c8"/>
                <text x="121" y="112" fill="#20242b" font-family="var(--font-family-serif)" font-size="34">A little learning,</text>
                <text x="121" y="151" fill="#20242b" font-family="var(--font-family-serif)" font-size="34">a little closer</text>
                <text x="124" y="181" fill="#5b6575" font-size="13">Your next musical discovery, by email</text>
                <path d="M124 202H361M124 213H320M124 224H345" stroke="#d7d2c8" stroke-width="3"/>
            </g>
            @break
        @case('infographics')
            <path fill="#edece7" d="M0 0H480V240H0z"/>
            <g transform="rotate(4 240 120)">
                <path d="M57 -20H436V270H57z" fill="#d4d1c9"/>
                <path d="M43 -27H422V260H43z" fill="#fffdf7"/>
                <text x="70" y="32" fill="#5b6575" font-size="11" letter-spacing="2">THE VISUAL REFERENCE</text>
                <text x="70" y="73" fill="#20242b" font-family="var(--font-family-serif)" font-size="34">Make music make sense</text>
                <path d="M70 92H395" stroke="#20242b" stroke-width="2"/>
                <text x="70" y="125" fill="#c53a1a" font-size="12" letter-spacing="2">DYNAMICS / SOFT TO LOUD</text>
                @foreach(['pp' => 91, 'p' => 157, 'mf' => 234, 'f' => 309, 'ff' => 375] as $dynamic => $position)
                    <text x="{{ $position }}" y="170" fill="#20242b" text-anchor="middle" font-family="var(--font-family-serif)" font-style="italic" font-weight="700" font-size="36">{{ $dynamic }}</text>
                @endforeach
                <path d="M75 198L390 185M75 198L390 211" stroke="#c53a1a" stroke-width="2"/>
            </g>
            @break
        @case('quizzes')
            <path fill="#ffd338" d="M0 0H480V240H0z"/>
            <circle cx="398" cy="120" r="104" stroke="#20242b" stroke-width="1.5"/>
            <circle cx="398" cy="120" r="88" stroke="#20242b" stroke-opacity=".25"/>
            <text x="398" y="169" fill="#20242b" text-anchor="middle" font-family="var(--font-family-serif)" font-size="151">?</text>
            <text x="28" y="43" fill="#20242b" font-size="12" letter-spacing="2">A LITTLE MUSICAL CHALLENGE</text>
            <text x="26" y="102" fill="#20242b" font-size="32" font-weight="600">Think you</text>
            <text x="26" y="141" fill="#20242b" font-size="32" font-weight="600">know music?</text>
            <path d="M28 187H190M28 197H190M28 207H190M28 217H190M28 227H190" stroke="#20242b" stroke-opacity=".25"/>
            <g fill="#20242b" stroke="#20242b" stroke-width="2">
                <ellipse cx="64" cy="217" rx="7" ry="5" transform="rotate(-20 64 217)"/><path d="M71 217V184"/>
                <ellipse cx="110" cy="207" rx="7" ry="5" transform="rotate(-20 110 207)"/><path d="M117 207V174"/>
                <ellipse cx="156" cy="212" rx="7" ry="5" transform="rotate(-20 156 212)"/><path d="M163 212V179"/>
            </g>
            @break
        @case('pianists')
            <path fill="#20242b" d="M0 0H480V240H0z"/>
            <path d="M359 0H480V240H359z" fill="#e85632"/>
            <text x="28" y="39" fill="#bcc3ce" font-size="12" letter-spacing="2">THE PEOPLE BEHIND THE PIANO</text>
            <g fill="#fffdf7" font-family="var(--font-family-serif)" font-size="39">
                <text x="26" y="96">Martha Argerich</text>
                <text x="26" y="143">Arthur Rubinstein</text>
                <text x="26" y="190">Vladimir Horowitz</text>
            </g>
            <text x="28" y="222" fill="#bcc3ce" font-size="11" letter-spacing="1.5">EXTRAORDINARY LIVES. UNFORGETTABLE MUSIC</text>
            <path d="M389 61H465V207H389z" fill="#fffdf7"/>
            @foreach([89, 118, 147, 176] as $keyY)
                <path d="M389 {{ $keyY }}H465" stroke="#20242b"/>
            @endforeach
            @foreach([80, 109, 167] as $keyY)
                <path d="M423 {{ $keyY }}H465V{{ $keyY + 17 }}H423z" fill="#20242b"/>
            @endforeach
            @break
    @endswitch
</svg>
