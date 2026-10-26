{{-- Vector characters stay sharp at every size; movement is handled by the results stylesheet. --}}
<div class="result-character" aria-hidden="true">
    <svg viewBox="0 0 300 190" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false">
        <g class="result-character__scene result-character__scene--excellent">
            <g class="result-character__variant" data-character-variant="0">
            <ellipse cx="150" cy="95" rx="93" ry="76" fill="#FFF6CF"/>
            <ellipse class="result-character__shadow" cx="148" cy="170" rx="40" ry="6" fill="#EADFAC"/>
            <g class="result-character__sparkles" stroke="#E7AC26" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <path d="M57 63v14m-7-7h14M241 106v12m-6-6h12"/>
                <path d="m224 36 3 7 8 1-6 5 2 8-7-4-7 4 2-8-6-5 8-1Z" fill="#FFE45E"/>
                <path d="m75 128 2 5 6 1-4 4 1 6-5-3-5 3 1-6-4-4 6-1Z" fill="#FFE45E"/>
            </g>
            <g class="result-character__confetti" stroke-width="5" stroke-linecap="round">
                <path d="m80 34 5 7M224 137l5 5" stroke="#B392EF"/>
                <path d="m52 106-5 3M187 22l3-7" stroke="#67C9B3"/>
                <path d="m250 75 5-3M109 20l-2-5" stroke="#F4A28C"/>
            </g>
            <g class="result-character__jumper" stroke="#624B26" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="m114 125-19-12-7-14m87 19 17-10 5-15M128 143l-8 17-11-1m47-17 9 13 11-3"/>
                <path d="M161 103V38q0-8 8-6l37 13q9 4 6 12l-2 9q-2 7-9 4l-26-10v64c0 21-21 30-42 30-23 0-37-12-34-28 3-19 31-31 50-27Z" fill="#FFD651"/>
                <path d="M168 39v13l34 12" stroke="#FFEBA0" stroke-width="5"/>
                <path d="M111 116q8-9 18-9" stroke="#FFF4C4" stroke-width="6"/>
                <path d="m122 123 4-4 4 4m15 0 4-4 4 4"/>
                <path d="M132 132q6 11 13 0Z" fill="#624B26" stroke-width="2"/>
                <ellipse cx="117" cy="132" rx="5" ry="3" fill="#F5A071" stroke="none"/>
                <ellipse cx="158" cy="132" rx="5" ry="3" fill="#F5A071" stroke="none"/>
            </g>
            </g>
            @foreach(['star-catcher', 'conductor', 'duet', 'crowned'] as $variantIndex => $variantName)
                @include('games.challenges.components.results.character-variant', ['resultTier' => 'excellent'])
            @endforeach
        </g>
        <g class="result-character__scene result-character__scene--strong">
            <g class="result-character__variant" data-character-variant="0">
            <ellipse cx="150" cy="95" rx="93" ry="76" fill="#E8F7EF"/>
            <ellipse class="result-character__shadow" cx="148" cy="170" rx="40" ry="6" fill="#C5E4D5"/>
            <g class="result-character__melody" stroke="#479F8B" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <path d="M66 76V56l12 4v10"/><ellipse cx="61" cy="77" rx="5" ry="4" fill="#79CEB5"/>
                <path d="M230 120V97l12 4"/><ellipse cx="225" cy="121" rx="5" ry="4" fill="#79CEB5"/>
                <path d="M224 52v10m-5-5h10M78 128v8m-4-4h8" stroke="#E7BC47"/>
            </g>
            <g class="result-character__dancer" stroke="#305C51" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="m111 126-17 1-8-9M128 147l-6 15h-10m42-17 7 16h11"/>
                <path class="result-character__wave" d="m173 121 17-6 3-20m-1 10 7-6"/>
                <path d="M161 103V38q0-8 8-6l37 13q9 4 6 12l-2 9q-2 7-9 4l-26-10v64c0 21-21 30-42 30-23 0-37-12-34-28 3-19 31-31 50-27Z" fill="#80D8B7"/>
                <path d="M168 39v13l34 12M111 116q8-9 18-9" stroke="#CFF4DC" stroke-width="5"/>
                <g class="result-character__eyes" fill="#305C51" stroke="none"><ellipse cx="126" cy="124" rx="3" ry="5"/><ellipse cx="148" cy="124" rx="3" ry="5"/></g>
                <path d="M131 134q6 6 12 0"/>
                <ellipse cx="116" cy="132" rx="5" ry="3" fill="#F2B1A0" stroke="none"/>
                <ellipse cx="158" cy="132" rx="5" ry="3" fill="#F2B1A0" stroke="none"/>
            </g>
            </g>
            @foreach(['pianist', 'drummer', 'duet', 'dj'] as $variantIndex => $variantName)
                @include('games.challenges.components.results.character-variant', ['resultTier' => 'strong'])
            @endforeach
        </g>
        <g class="result-character__scene result-character__scene--encouraging">
            <g class="result-character__variant" data-character-variant="0">
            <ellipse cx="150" cy="95" rx="93" ry="76" fill="#F1EDFC"/>
            <ellipse cx="148" cy="170" rx="40" ry="6" fill="#DED5F1"/>
            <g class="result-character__listening" stroke="#AC96D8" stroke-width="3" stroke-linecap="round">
                <path d="M67 99q-10 13 0 26m-9-33q-16 20 0 40M232 92q10 13 0 26m9-33q16 20 0 40"/>
            </g>
            <g class="result-character__listener" stroke="#57456E" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M126 148v14h-12m40-14v14h12"/>
                <path d="M161 103V38q0-8 8-6l37 13q9 4 6 12l-2 9q-2 7-9 4l-26-10v64c0 21-21 30-42 30-23 0-37-12-34-28 3-19 31-31 50-27Z" fill="#BEA5EC"/>
                <path d="M168 39v13l34 12M111 116q8-9 18-9" stroke="#E9DBFF" stroke-width="5"/>
                <path d="M99 121v-4c0-35 77-43 79-4v9" stroke-width="7"/>
                <rect x="93" y="115" width="12" height="21" rx="6" fill="#FFF9E9"/>
                <rect x="173" y="115" width="12" height="21" rx="6" fill="#FFF9E9"/>
                <path d="M120 125q4 5 8 0m15 0q4 5 8 0M132 135q5 4 10 0"/>
                <ellipse cx="116" cy="135" rx="5" ry="3" fill="#F1B2BE" stroke="none"/>
                <ellipse cx="157" cy="135" rx="5" ry="3" fill="#F1B2BE" stroke="none"/>
            </g>
            <path class="result-character__heart" d="M219 63s-16-9-16-17c0-8 11-10 16-3 5-7 16-5 16 3 0 8-16 17-16 17Z" fill="#F4B5C5" stroke="#B87E99" stroke-width="2.5" stroke-linejoin="round"/>
            </g>
            @foreach(['reader', 'metronome', 'pianist', 'tea-break'] as $variantIndex => $variantName)
                @include('games.challenges.components.results.character-variant', ['resultTier' => 'encouraging'])
            @endforeach
        </g>
    </svg>
</div>
