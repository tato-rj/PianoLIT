@component('games.challenges.components.modal', ['title' => \App\Games\GameIcon::render('gear').'Game settings', 'id' => str_slug($settings->gameName()).'-settings-modal'])
<form id="note-match-settings" method="GET" action="{{ $settings->gameUrl() }}">
  @component('games.challenges.components.settings.section', ['title' => 'SETUP'])
    @include('games.challenges.components.settings.value-range', ['label' => 'Number of rounds', 'name' => 'numOfChallenges', 'min' => 2, 'max' => 12, 'id' => str_slug($settings->gameName()).'-numOfChallenges', 'value' => $settings->options('numOfChallenges')])
    @include('games.challenges.components.settings.toggle', ['label' => 'Practice mode', 'name' => 'practiceMode'])
  @endcomponent

  @component('games.challenges.components.settings.section', ['title' => 'MATERIAL'])
    @include('games.challenges.components.settings.multichoice', ['label' => 'Clefs', 'name' => 'clefs', 'options' => $settings->getClefs(), 'game' => $settings->gameName(), 'ucfirst' => true])
    <hr class="opacity-1">
    @include('games.challenges.components.settings.toggle', ['label' => 'Allow accidentals', 'name' => 'allowAccidentals'])
  @endcomponent

  @component('games.challenges.components.settings.section', ['title' => 'PREFERENCES'])
    @include('games.challenges.components.settings.toggle', ['label' => 'Sound effects', 'name' => 'sound'])
    @include('games.challenges.components.settings.toggle', ['label' => 'Show note names', 'name' => 'showNoteNames'])
    @include('games.challenges.components.settings.toggle', ['label' => 'Show names of each line', 'name' => 'showLineNames'])
    @include('games.challenges.components.settings.toggle', ['label' => 'Use solfege', 'name' => 'solfege'])
  @endcomponent

  @component('games.challenges.components.settings.bonus')
    @include('games.challenges.components.settings.toggle', ['label' => 'Time limit', 'name' => 'timer', 'icon' => 'stopwatch'])
  @endcomponent

  <button type="submit" class="btn btn-primary w-100">Start new game</button>
  @include('games.challenges.components.settings.duel')
</form>
@endcomponent
