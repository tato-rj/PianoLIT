<div>
	@if($item->isCancelled)
  	<span class="text-nowrap text-red"><i class="fas fa-times-circle mr-2"></i>Cancelled</span>
  	@elseif($item->isCompleted)
  	<span class="text-nowrap text-green"><i class="fas fa-check-circle mr-2"></i>Completed</span>
  	@else
  	<span class="text-nowrap text-warning"><i class="fas fa-hourglass-half mr-2"></i>On lesson {{$item->previousLessonIndex + 1}}</span>
  	@endif
</div>