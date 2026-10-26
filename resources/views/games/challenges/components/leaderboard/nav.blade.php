@php($leaderboardRange = request('range') === 'week' ? 'week' : 'all')
<div class="btn-group" role="group" aria-label="Leaderboard period">
    @foreach(['week' => 'Last 7 days', 'all' => 'All time'] as $range => $label)
        <div>
            <input singlechoice
                data-url="{{route($leaderboardRoute ?? 'games.leaderboard.show', ['range' => $range, 'game' => $name])}}"
                name="{{str_slug($name)}}-leaderboard-range" type="radio" value="{{$range}}"
                class="btn-check" id="{{str_slug($name)}}-leaderboard-range-{{$range}}"
                {{ $leaderboardRange === $range ? 'checked' : '' }} autocomplete="off">
            <label class="btn btn-sm px-3 {{$leaderboardRange === $range ? 'btn-secondary' : 'btn-white'}}" for="{{str_slug($name)}}-leaderboard-range-{{$range}}">{{$label}}</label>
        </div>
    @endforeach
</div>
