@component('games.challenges.components.modal', ['title' => \App\Games\GameIcon::render('gear').'Game settings', 'id' => str_slug($settings->gameName()).'-settings-modal'])
<form id="beat-hero-settings" method="GET" action="{{ $settings->gameUrl() }}">
  @component('games.challenges.components.settings.section', ['title' => 'SETUP'])
    @include('games.challenges.components.settings.value-range', ['label' => 'Number of rounds', 'name' => 'numOfChallenges', 'min' => 2, 'max' => 12, 'id' => str_slug($settings->gameName()).'-numOfChallenges', 'value' => $settings->options('numOfChallenges')])

    @include('games.challenges.components.settings.toggle', ['label' => 'Practice mode', 'name' => 'practiceMode'])
  @endcomponent

  @component('games.challenges.components.settings.section', ['title' => 'MATERIAL'])
    <fieldset class="mb-1">
      <legend class="h6 mb-1">Rhythm symbols</legend>
      <p class="small text-light mb-2">Choose at least 2</p>

      <div class="beat-hero-symbol-picker" data-beat-hero-symbol-picker>
        @foreach($settings->figureChoices() as $figureId => $figureLabel)
          <div class="beat-hero-symbol-choice">
            <input
              id="beat-hero-figure-{{$figureId}}"
              class="beat-hero-symbol-input"
              type="checkbox"
              name="figures[]"
              value="{{$figureId}}"
              {{in_array($figureId, $settings->options('figures'), true) ? 'checked' : null}}>
            <label
              class="beat-hero-symbol-option"
              for="beat-hero-figure-{{$figureId}}"
              title="{{$figureLabel}}"
              aria-label="{{$figureLabel}}">
              <span class="beat-hero-symbol-figure" data-beat-hero-figure-thumbnail="{{$figureId}}" aria-hidden="true"></span>
              <span class="beat-hero-symbol-check" aria-hidden="true">✓</span>
            </label>
          </div>
        @endforeach
      </div>

      <p class="beat-hero-symbol-message small fw-bold mb-0 mt-2" data-beat-hero-symbol-message aria-live="polite"></p>
    </fieldset>
  @endcomponent

  @component('games.challenges.components.settings.section', ['title' => 'PREFERENCES'])
    <input type="hidden" name="sound" value="0">
    @include('games.challenges.components.settings.toggle', ['label' => 'Sound effects', 'name' => 'sound'])

    @include('games.challenges.components.settings.value-range', [
      'label' => 'Cards to play',
      'name' => 'numOfCards',
      'id' => 'beat-hero-card-count',
      'min' => 2,
      'max' => 6,
      'step' => 1,
      'value' => $settings->options('numOfCards'),
    ])

    @include('games.challenges.components.settings.value-range', [
      'label' => 'Metronome speed (bpm)',
      'name' => 'bpm',
      'id' => 'beat-hero-bpm',
      'min' => 50,
      'max' => 160,
      'step' => 5,
      'value' => $settings->options('bpm'),
      'minLabel' => '50 BPM',
      'maxLabel' => '160 BPM',
    ])
  @endcomponent

  <button type="submit" class="btn btn-primary w-100">Start new game</button>
  @include('games.challenges.components.settings.duel')
</form>
@endcomponent

@once
  @push('scripts')
    <script src="{{ mix('js/games/beathero.js') }}"></script>
  @endpush
@endonce
