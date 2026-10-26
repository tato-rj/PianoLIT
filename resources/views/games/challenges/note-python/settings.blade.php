@component('games.challenges.components.modal', ['title' => \App\Games\GameIcon::render('gear').'Game settings', 'id' => str_slug($settings->gameName()).'-settings-modal'])
<form id="intervals-settings" method="GET" action="{{ $settings->gameUrl() }}">
  @component('games.challenges.components.settings.section', ['title' => 'SETUP'])
    @include('games.challenges.components.settings.value-range', ['label' => 'Number of rounds', 'name' => 'numOfChallenges', 'min' => 2, 'max' => 12, 'id' => str_slug($settings->gameName()).'-numOfChallenges', 'value' => $settings->options('numOfChallenges')])
    @include('games.challenges.components.settings.toggle', ['label' => 'Practice mode', 'name' => 'practiceMode'])
    @include('games.challenges.components.settings.value-range', [
      'label' => 'Metronome speed (bpm)',
      'name' => 'bpm',
      'id' => 'note-python-bpm',
      'min' => 50,
      'max' => 160,
      'step' => 5,
      'value' => $settings->options('bpm'),
      'minLabel' => '50 BPM',
      'maxLabel' => '160 BPM',
    ])
  @endcomponent

  @component('games.challenges.components.settings.section', ['title' => 'MATERIAL'])
    @include('games.challenges.components.settings.multichoice', ['label' => 'Intervals', 'name' => 'intervals', 'options' => $settings->getIntervals(), 'game' => $settings->gameName()])
    <hr class="opacity-1">
    @include('games.challenges.components.settings.toggle', ['label' => 'Allow accidentals', 'name' => 'allowAccidentals'])
    @include('games.challenges.components.settings.toggle', ['label' => 'Specify a direction', 'name' => 'strictDirection'])
  @endcomponent

  @component('games.challenges.components.settings.section', ['title' => 'PREFERENCES'])
    @include('games.challenges.components.settings.toggle', ['label' => 'Sound effects', 'name' => 'sound'])
    @include('games.challenges.components.settings.toggle', ['label' => 'Use solfege', 'name' => 'solfege'])
  @endcomponent

  @component('games.challenges.components.settings.bonus')
    @include('games.challenges.components.settings.toggle', ['label' => 'Show bombs', 'name' => 'showBombs', 'icon' => 'bomb'])
    @include('games.challenges.components.settings.toggle', ['label' => 'Real walls', 'name' => 'realWalls', 'icon' => 'square'])
    @include('games.challenges.components.settings.toggle', ['label' => 'Speed up each round', 'name' => 'speedUpEachRound', 'icon' => 'gauge-high'])
  @endcomponent

  <button type="submit" class="btn btn-primary w-100">Start new game</button>
  @include('games.challenges.components.settings.duel')
</form>
@endcomponent
