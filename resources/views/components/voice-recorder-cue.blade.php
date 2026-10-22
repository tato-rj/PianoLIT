@php
    $cueState = $state ?? 'waiting';
    $cueLabels = ['waiting' => 'Wait', 'speaking' => 'Speak now', 'success' => 'Recorded', 'error' => 'Try again', 'review' => ''];
    $cuePrompts = ['waiting' => 'Wait for green', 'speaking' => 'Say “one”', 'success' => 'Got it!', 'error' => 'Let’s try one again', 'review' => 'How does it sound?'];
@endphp
<div class="voice-recorder__cue text-center mb-3" data-voice-cue data-state="{{ $cueState }}">
    <div class="voice-recorder__label badge rounded-pill mb-3" aria-hidden="true">
        <span class="voice-recorder__state-icon" data-cue-icon="waiting">@icon('hourglass', ['mr' => 0])</span>
        <span class="voice-recorder__state-icon" data-cue-icon="speaking">@icon('mic', ['mr' => 0])</span>
        <span class="voice-recorder__state-icon" data-cue-icon="success">@icon('check', ['mr' => 0])</span>
        <span class="voice-recorder__state-icon" data-cue-icon="error">@icon('circle-alert', ['mr' => 0])</span>
        <span data-voice-cue-label>{{ $cueLabels[$cueState] }}</span>
    </div>
    <div class="voice-recorder__visual mx-auto mb-3" data-voice-visual aria-hidden="true">
        <span class="voice-recorder__number" data-voice-number>{{ $number ?? 1 }}</span>
    </div>
    <h2 class="h4 mb-2" data-voice-prompt aria-live="polite" aria-atomic="true">{{ $cuePrompts[$cueState] }}</h2>
    <p class="small mb-0" data-voice-countdown @unless($cueState === 'waiting') hidden @endunless aria-hidden="true">Ready in 3</p>
</div>
