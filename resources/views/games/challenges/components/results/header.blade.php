<div class="results-topline">
    @include('games.challenges.components.identity')
    <div class="btn-floating flex-shrink-0">
        @if($duelHeader ?? false)
            <button type="button" class="btn btn-white btn-sm text-nowrap" data-duel-leave>All games @include('games.challenges.components.icon', ['icon' => 'arrow-up-right-from-square', 'mr' => 0])</button>
        @else
            <a href="{{route('games.home')}}" class="btn btn-white btn-sm text-nowrap">All games @include('games.challenges.components.icon', ['icon' => 'arrow-up-right-from-square', 'mr' => 0])</a>
        @endif
    </div>
</div>
