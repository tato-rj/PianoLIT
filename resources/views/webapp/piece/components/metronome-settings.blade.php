<div id="score-metronome-settings" class="dropdown-menu dropdown-menu-end score-metronome-settings-menu p-3 overflow-auto" role="group" aria-labelledby="score-metronome-settings-toggle">
    <fieldset class="mb-3">
        <legend class="h6 mb-2">Beat count</legend>
        @include('components.forms.segmented-control', ['id' => 'score-metronome-beats', 'name' => 'metronome-beats', 'label' => 'Beat count', 'options' => [1 => '1', 2 => '2', 3 => '3', 4 => '4'], 'value' => 1, 'dataAttribute' => 'metronome-beats'])
    </fieldset>
    <div>
        <label for="score-metronome-sound" class="h6 mb-2">Sound</label>
        <select id="score-metronome-sound" data-metronome-sound class="form-select"
            @foreach(['one', 'two', 'three', 'four'] as $number => $word)
                data-count-url-{{ $number + 1 }}="{{ asset('audio/metronome/count-'.$word.'-v2.wav') }}"
                data-female-count-url-{{ $number + 1 }}="{{ asset('audio/metronome/count-female-'.$word.'-v1.wav') }}"
            @endforeach
        >
            <option value="woodblock">Woodblock</option>
            <option value="click">Click</option>
            <option value="claves">Claves</option>
            <option value="hi-hat">Hi-hat</option>
            <option value="voice">Male counting</option>
            <option value="female-voice">Female counting</option>
        </select>
    </div>
    <p data-metronome-settings-status class="small text-muted mt-3 mb-0" role="status" aria-live="polite" hidden></p>
    <p data-metronome-save-status class="small text-danger mt-3 mb-2" role="status" aria-live="polite" hidden></p>
    <button type="button" data-metronome-save-retry class="btn btn-sm btn-outline-primary" hidden>Retry saving</button>
</div>
