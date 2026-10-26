@php
    $soundId = $id ?? 'metronome-sound';
    $soundRecordings = $recordings ?? [];
    $soundCanRecord = $canRecord ?? auth('web')->check();
    $soundChoices = ['click' => 'Classic click', 'woodblock' => 'Woodblock', 'beep' => 'Beep', 'cowbell' => 'Cowbell'];
    if (! count($soundRecordings) && $soundCanRecord) $soundChoices['my-voice'] = 'My voice';
    $recordedChoices = [];
    foreach ($soundRecordings as $recording) $recordedChoices['voice:'.$recording['id']] = $recording['name'];
@endphp
<div class="metronome-sound dropdown" data-sound-menu data-settings-field>
    <label class="h6 mb-2" id="{{ $soundId }}-label" for="{{ $soundId }}-toggle">Sound</label>
    <div hidden>
        @select(['bag' => 'default', 'id' => $soundId, 'name' => $soundId, 'label' => null, 'grid' => 'mb-0', 'choices' => $soundChoices, 'choiceGroups' => $recordedChoices ? ['Recorded voices' => $recordedChoices] : [], 'value' => $value ?? 'click', 'required' => false])
    </div>
    <button type="button" class="form-control form-control-select-trigger text-start" id="{{ $soundId }}-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-sound-toggle aria-haspopup="dialog" aria-expanded="false" aria-controls="{{ $soundId }}-menu" aria-labelledby="{{ $soundId }}-label {{ $soundId }}-value">
        <span class="d-block text-truncate" id="{{ $soundId }}-value" data-sound-label>{{ $recordedChoices[$value ?? ''] ?? $soundChoices[$value ?? 'click'] ?? 'Classic click' }}</span>
    </button>
    <div class="dropdown-menu w-100 p-2" id="{{ $soundId }}-menu" data-sound-popup role="dialog" aria-label="Choose a sound">
        <div class="metronome-sound__options" data-sound-options>
            <div role="group" aria-label="Instruments">
                @foreach(['click' => 'Classic click', 'woodblock' => 'Woodblock', 'beep' => 'Beep', 'cowbell' => 'Cowbell'] as $soundValue => $soundLabel)
                    <button type="button" class="dropdown-item" data-sound-value="{{ $soundValue }}" aria-pressed="{{ ($value ?? 'click') === $soundValue ? 'true' : 'false' }}">{{ $soundLabel }}</button>
                @endforeach
            </div>
            @if($soundCanRecord)
                <div class="dropdown-header mt-2" id="{{ $soundId }}-voices-label">Recorded voices</div>
                <div role="group" aria-labelledby="{{ $soundId }}-voices-label" data-sound-voices>
                    @foreach($soundRecordings as $soundRecording)
                        @include('metronome.components.sound-voice', ['recording' => $soundRecording, 'selected' => ($value ?? 'click') === 'voice:'.$soundRecording['id']])
                    @endforeach
                </div>
                <button type="button" class="dropdown-item" data-sound-empty @if(count($soundRecordings)) hidden @endif>My voice</button>
                <template data-sound-template>@include('metronome.components.sound-voice', ['recording' => ['id' => '', 'name' => '']])</template>
            @endif
        </div>
    </div>
    <div class="mt-2">
        @if($soundCanRecord)
            <button type="button" class="btn btn-secondary btn-sm w-100" data-voice-start @unless($preview ?? false) disabled @endunless>@icon('mic')<span data-voice-start-label>New recording</span></button>
        @else
            <a class="btn btn-secondary btn-sm w-100" href="{{ route('metronome.voice.start') }}">@icon('mic')New recording</a>
        @endif
    </div>
</div>
