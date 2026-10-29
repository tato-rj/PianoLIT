@if($quiz->level)
<span>@icon('chart-no-axes-column-increasing'){{ ucfirst($quiz->level->name) }}</span>
@endif
