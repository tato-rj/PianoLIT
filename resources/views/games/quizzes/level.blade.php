@if($quiz->level)
<span>@icon('circle-help'){{ ucfirst($quiz->level->name) }}</span>
@endif
