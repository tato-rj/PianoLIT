<div id="controls" class="mb-5">
  <div class="d-flex flex-column">

    @if(isset($type) && $type == 'play')
    {{-- <div class="position-relative"> --}}
      @include('games.challenges.components.play', ['playLabel' => $playLabel ?? 'Play'])
    {{-- </div> --}}
    @else
      <div class="position-relative">
        @if(isset($type) && $type == 'play-check')
        @include('games.challenges.components.play', ['playLabel' => $playLabel ?? 'Play'])
        @endif
        @isset($instructions)
        @if(!empty($instructions))
        @include('games.challenges.components.instructions', ['instructionContent' => $instructions])
        @endif
        @endisset
        <div id="check" class="btn-floating w-100 invisible mb-3" style="display: none">
          <button state="waiting" class="btn w-100"></button>
        </div>
        <div id="help" class="btn-floating w-100 mb-3" style="display: none">
          <button class="btn btn-blue w-100">@include('games.challenges.components.icon', ['icon' => 'wand-magic-sparkles'])See answer</button>
        </div>
        <div id="skip" class="btn-floating w-100 mb-3" style="display: none;">
          <button class="btn btn-red w-100">Next round</button>
        </div>
        <div id="play-note" class="w-100 invisible mb-3" style="display: none">
          <div id="play-note-status" class="rounded border w-100 mb-3 text-center" role="status" aria-live="polite" style="display: none; padding: .375rem .75rem">
          </div>
          <div id="play-note-start" class="btn-floating w-100">
            <button type="button" class="btn btn-blue w-100">@include('games.challenges.components.icon', ['icon' => 'microphone'])Tap here and play the note</button>
          </div>
        </div>
      </div>
    @endif

    <div id="continue" class="btn-floating w-100 mb-3" style="display: none;">
      <button class="btn btn-green w-100">Continue</button>
    </div>

    @unless(isset($duelState))
    <div class="d-flex">
      @unless($settings->gameName() == 'Open Staff')
      <div class="btn-floating mr-1 w-100">
        <button data-bs-toggle="modal" data-bs-target="#leaderboard-{{str_slug($settings->gameName())}}-modal" class="btn btn-white w-100">@include('games.challenges.components.icon', ['icon' => 'ranking-star'])Leaderboard</button>
      </div>
      @endunless

      <div class="btn-floating ml-1 w-100" data-duel-settings>
          <button data-bs-toggle="modal" data-bs-target="#{{str_slug($settings->gameName())}}-settings-modal" class="btn btn-white w-100" {{ isset($duelState) ? 'disabled' : '' }}>@include('games.challenges.components.icon', ['icon' => 'gear'])Settings</button>
        </div>
      </div>
{{--       @unless($settings->gameName() == 'Open Staff')
      <button type="button" data-duel-join class="btn btn-white w-100 mt-3">Join a Duel</button>
      @endunless --}}
    @endunless
  </div>
</div>
@isset($duelState)

  <button type="button" data-duel-leave class="btn-raw text-danger w-100">@include('games.challenges.components.icon', ['icon' => 'arrow-right-from-bracket'])Leave Duel</button>

@endisset
