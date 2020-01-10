@php($lastActive = $item->lastActive())
<span class="invisible position-absolute">{{$item->last_active_at ? $item->last_active_at->timestamp : null}}</span>
<div class="{{! is_null($lastActive) && $lastActive->isAfter(now()->subHours(12)) ? 'text-success' : null}}">
  <span class="position-absolute invisible">{{! is_null($lastActive) ? $lastActive->timestamp : 0}}</span>
  {{$lastActive ? $lastActive->diffForHumans() : null}}
</div>