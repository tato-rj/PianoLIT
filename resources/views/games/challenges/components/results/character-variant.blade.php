@php
    $palette = [
        'excellent' => ['ink' => '#624B26', 'body' => '#FFD651', 'shine' => '#FFF0AE', 'back' => '#FFF6CF', 'shadow' => '#EADFAC'],
        'strong' => ['ink' => '#305C51', 'body' => '#80D8B7', 'shine' => '#CFF4DC', 'back' => '#E8F7EF', 'shadow' => '#C5E4D5'],
        'encouraging' => ['ink' => '#57456E', 'body' => '#BEA5EC', 'shine' => '#E9DBFF', 'back' => '#F1EDFC', 'shadow' => '#DED5F1'],
    ][$resultTier];
    $seated = in_array($variantName, ['pianist', 'reader', 'dj']);
@endphp
<g class="result-character__variant result-vignette result-vignette--{{$variantName}}" data-character-variant="{{$variantIndex + 1}}">
    <ellipse cx="150" cy="95" rx="93" ry="76" fill="{{$palette['back']}}"/>
    <ellipse cx="148" cy="170" rx="44" ry="6" fill="{{$palette['shadow']}}"/>

    <g class="result-vignette__ambient" stroke="{{$palette['ink']}}" stroke-opacity=".45" stroke-width="2.5" stroke-linecap="round">
        @if($resultTier === 'excellent')
            <path d="m66 52 5 6m9-25 3 7m145 91 5 4m10-68 5-3"/>
            <path d="M70 117v10m-5-5h10M230 40v10m-5-5h10"/>
        @else
            <path d="M70 79V61l11 4M225 51V37l9 3"/>
            <ellipse cx="65" cy="80" rx="5" ry="3" fill="{{$palette['body']}}"/>
            <ellipse cx="221" cy="52" rx="4" ry="3" fill="{{$palette['body']}}"/>
        @endif
    </g>

    <g class="result-vignette__figure" stroke="{{$palette['ink']}}" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M128 145l-6 17h-10m43-17 8 17h10"/>
        @if($seated)
            <path class="result-vignette__left-hand" d="m104 125-13 10 14 15"/>
            <path class="result-vignette__right-hand" d="m173 124 14 12-13 14"/>
        @elseif($variantName === 'conductor')
            <path d="m105 126-19-13-4-13"/>
            <g class="result-vignette__baton">
                <path d="m173 121 22-9 6-12"/>
                <path d="m195 103 25-35" stroke="#8F7C61" stroke-width="5"/>
                <path d="m204 90 16-22" stroke="#FFFDF2" stroke-width="3"/>
            </g>
        @elseif($variantName === 'drummer')
            <path d="m106 127-10 12 13 7"/>
            <g class="result-vignette__drumstick">
                <path d="m173 123 21-10 8 4"/>
                <path d="m199 114 28 12" stroke="#C69A62" stroke-width="4"/>
            </g>
        @elseif($variantName === 'star-catcher')
            <path d="m105 122-15-9-2-11m86 19 18-9 6-11"/>
        @elseif($variantName === 'tea-break')
            <path d="m105 129-11 10 10 4m69-17 16 12 12-5"/>
        @elseif($variantName === 'duet')
            <path d="m105 124-17-8-5-10m91 17 16 11 10-3"/>
        @else
            <path d="m105 128-13 5-7-9m89 1 16-9 9 4"/>
        @endif

        <path d="M161 103V38q0-8 8-6l37 13q9 4 6 12l-2 9q-2 7-9 4l-26-10v64c0 21-21 30-42 30-23 0-37-12-34-28 3-19 31-31 50-27Z" fill="{{$palette['body']}}"/>
        <path d="M168 39v13l34 12M111 116q8-9 18-9" stroke="{{$palette['shine']}}" stroke-width="5"/>
        <g class="result-vignette__eyes">
            @if($resultTier === 'excellent')
                <path d="m121 124 4-4 4 4m15 0 4-4 4 4"/>
            @elseif($resultTier === 'encouraging')
                <path d="M121 125q4 5 8 0m15 0q4 5 8 0"/>
            @else
                <ellipse cx="125" cy="124" rx="3" ry="5" fill="{{$palette['ink']}}" stroke="none"/>
                @if($variantName === 'dj' || $variantName === 'duet')
                    <path d="m144 124 7-2"/>
                @else
                    <ellipse cx="148" cy="124" rx="3" ry="5" fill="{{$palette['ink']}}" stroke="none"/>
                @endif
            @endif
        </g>
        @if($resultTier === 'excellent')
            <path d="M131 133q7 10 14 0Z" fill="{{$palette['ink']}}" stroke-width="2"/>
        @else
            <path d="M132 134q5 5 11 0"/>
        @endif
        <ellipse cx="115" cy="132" rx="5" ry="3" fill="#F0AAA0" stroke="none"/>
        <ellipse cx="159" cy="132" rx="5" ry="3" fill="#F0AAA0" stroke="none"/>

        @if($variantName === 'crowned')
            <path d="m111 104-4-22 14 9 12-16 12 16 14-9-5 22Z" fill="#F5B376" stroke="#9C642F" stroke-width="2.5"/>
            <path d="M115 99h35" stroke="#FFDF95"/>
            <circle cx="133" cy="92" r="3" fill="#FFF8D8" stroke="none"/>
        @elseif($variantName === 'conductor')
            <path d="m130 149-9-5v12l9-5 9 5v-12Z" fill="#C38685" stroke-width="2"/>
        @elseif($variantName === 'dj')
            <path d="M97 120c-2-38 80-42 82-5" stroke-width="7"/>
            <rect x="91" y="114" width="12" height="21" rx="6" fill="#FFF9E9"/>
            <rect x="174" y="114" width="12" height="21" rx="6" fill="#FFF9E9"/>
        @endif
    </g>

    @if($variantName === 'star-catcher')
        <g class="result-vignette__star" stroke="#B87F26" stroke-width="2.5" stroke-linejoin="round">
            <path d="m205 64 8 16 18 3-13 13 3 19-16-9-16 9 3-19-13-13 18-3Z" fill="#FFE890"/>
            <path d="m205 74 4 9 10 2" stroke="#FFF9D8"/>
        </g>
    @elseif($variantName === 'duet')
        <g transform="translate(148 81) scale(.5)">
            <g class="result-vignette__friend" stroke="{{$palette['ink']}}" stroke-width="5" stroke-linecap="round" stroke-linejoin="round">
                <path d="m92 119-13-12m76 16 17-11m-60 31-5 18h-10m42-18 5 18h11"/>
                <path d="M140 105V45l34 10v18l-22-7v61c0 24-57 29-62 7-5-21 27-38 50-29Z" fill="{{$resultTier === 'excellent' ? '#E5ABCB' : '#A1CBEA'}}"/>
                <path d="m105 125 5-4 5 4m14 0 5-4 5 4m-21 9q6 6 12 0"/>
            </g>
        </g>
    @elseif($variantName === 'pianist')
        <g stroke="{{$palette['ink']}}" stroke-width="2.5" stroke-linejoin="round">
            <rect x="85" y="147" width="116" height="23" rx="5" fill="#FFFCF3"/>
            <path d="M100 148v21m15-21v21m14-21v21m14-21v21m14-21v21m14-21v21m14-21v21" stroke-width="1.5"/>
            <path d="M99 148v11m15-11v11m29-11v11m14-11v11m14-11v11" stroke-width="6"/>
            <g class="result-vignette__keys" fill="{{$palette['body']}}" stroke="none"><rect x="117" y="161" width="9" height="6" rx="2"/><rect x="173" y="161" width="9" height="6" rx="2"/></g>
        </g>
    @elseif($variantName === 'drummer')
        <g stroke="#517589" stroke-width="2.5" stroke-linejoin="round">
            <path d="M190 130v25c0 10 48 10 48 0v-25Z" fill="#A9D5E9"/>
            <path d="m193 137 8 18 12-18 12 18 10-18" stroke="#FFF9E9"/>
            <ellipse class="result-vignette__drumhead" cx="214" cy="130" rx="24" ry="7" fill="#FFF9E9"/>
        </g>
    @elseif($variantName === 'dj')
        <g stroke="{{$palette['ink']}}" stroke-width="2.5">
            <rect x="83" y="148" width="120" height="26" rx="6" fill="#B3D9DA"/>
            <g class="result-vignette__record result-vignette__record--left"><circle cx="108" cy="161" r="10" fill="#477075"/><path d="M108 154v7h7" stroke="#FFEDAD" stroke-width="2"/></g>
            <g class="result-vignette__record result-vignette__record--right"><circle cx="178" cy="161" r="10" fill="#477075"/><path d="M178 154v7h7" stroke="#FFEDAD" stroke-width="2"/></g>
            <path d="M135 155v12m8-12v12m8-12v12" stroke-width="2"/>
        </g>
    @elseif($variantName === 'reader')
        <g class="result-vignette__book" stroke="{{$palette['ink']}}" stroke-width="2.5" stroke-linejoin="round">
            <path d="M98 143q20-8 40 2 20-10 40-2v24q-20-7-40 2-20-9-40-2Z" fill="#FFF9E9"/>
            <path d="M138 145v24"/>
            <path d="M105 151h23m-23 6h23m20-6h22m-22 6h22" stroke="#C7B6A2" stroke-width="1.5"/>
            <path class="result-vignette__page" d="M142 145q11-8 25-8l-3 24q-11 0-22 8" fill="#FFFDF7"/>
        </g>
    @elseif($variantName === 'metronome')
        <g stroke="#8B728F" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="m196 164 10-53h18l10 53Z" fill="#E7CBDA"/>
            <path d="M193 169h44m-29-12h13"/>
            <g class="result-vignette__pendulum"><path d="M215 152V104"/><path d="M211 119h8v7h-8Z" fill="#8B728F"/></g>
            <circle cx="215" cy="152" r="2" fill="#8B728F"/>
        </g>
    @elseif($variantName === 'tea-break')
        <g stroke="#8B728F" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M222 132h5c13 0 12 15-5 15"/>
            <path d="M197 130h26v17q0 9-13 9t-13-9Z" fill="#FFF3CE"/>
            <path class="result-vignette__steam" d="M204 121q-7-6 0-12m10 12q-7-6 0-12" stroke="#BAACCA"/>
            <path d="M192 161h36"/>
        </g>
    @endif
</g>
