<div>
	@if($item->isCancelled)
    <span class="text-nowrap text-red">@icon('circle-x', ['mr' => 2])Cancelled</span>
  	@elseif($item->isCompleted)
    <span class="text-nowrap text-green">@icon('circle-check', ['mr' => 2])Completed</span>
  	@else
    <span class="text-nowrap text-warning">@icon('hourglass', ['mr' => 2])On lesson {{$item->previousLessonIndex + 1}}</span>
  	@endif
</div>