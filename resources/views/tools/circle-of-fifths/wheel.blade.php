<svg id="circle" role="group" aria-label="Choose a musical key" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 600" class="w-100 d-block">
    <g class="circle-rings" aria-hidden="true">
        <circle class="circle-rings__surface" cx="300" cy="300" r="280" />
        <circle class="circle-rings__divider" cx="300" cy="300" r="198" />
        <circle class="circle-rings__center" cx="300" cy="300" r="128" />
        @for($index = 0; $index < 12; $index++)
            @php
                $angle = deg2rad($index * 30 - 105);
            @endphp
            <line class="circle-rings__divider" x1="{{ 300 + 130 * cos($angle) }}" y1="{{ 300 + 130 * sin($angle) }}" x2="{{ 300 + 280 * cos($angle) }}" y2="{{ 300 + 280 * sin($angle) }}" />
        @endfor
    </g>
    <g id="letters">
        @foreach(['c', 'g', 'd', 'a', 'e', 'b', 'f#', 'db', 'ab', 'eb', 'bb', 'f'] as $key)
            @php
                $circleKey = $keys->find($key)->setMajor();
                $majorLabel = str_replace(['#', 'b'], ['♯', '♭'], explode(' ', $circleKey->getMajorKey())[0]);
                $minorLabel = str_replace(['#', 'b'], ['♯', '♭'], explode(' ', $circleKey->getMinorKey())[0]) . 'm';
                $angle = deg2rad($loop->index * 30 - 90);
            @endphp
            <g class="{{ $key === 'c' ? 'key' : '' }}" @include('tools.circle-of-fifths.attributes', ['key' => $key])>
                @foreach([['radius' => 238, 'label' => $majorLabel, 'class' => 'circle-major-label'], ['radius' => 164, 'label' => $minorLabel, 'class' => 'circle-minor-label']] as $label)
                    <g transform="translate({{ 300 + $label['radius'] * cos($angle) }} {{ 300 + $label['radius'] * sin($angle) }})" aria-hidden="true">
                        <g class="circle-key-label"><text class="{{ $label['class'] }}" text-anchor="middle" dominant-baseline="central">{{ $label['label'] }}</text></g>
                    </g>
                @endforeach
            </g>
        @endforeach
    </g>
</svg>
