<span class="{{$lastActive && $lastActive->isAfter(now()->subHours(12)) ? 'text-success' : null}} text-nowrap">
  {{$lastActive ? $lastActive->diffForHumans() : null}}
</span>
