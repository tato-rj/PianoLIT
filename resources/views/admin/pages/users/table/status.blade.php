  <div class="text-truncate">
    @if($item->membership()->exists())
      @if($item->membership->source->expired())
      <span class="text-muted"><i><small>validated {{$item->membership->source->validated_at->diffForHumans()}}</small></i></span>
      @else
      <div><i class="fas fa-credit-card"></i></div>
      @endif
    @else
      Guest
    @endif
  </div>