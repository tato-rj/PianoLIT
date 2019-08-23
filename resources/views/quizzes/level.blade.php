<div class="text-center mb-5">
	<div class="badge badge-pill badge-light mb-1"><small>DISCLAIMER</small></div>
	<label class="d-block">This quiz is <strong>{{$quiz->level->name}}</strong></label>
	<div class="w-100 d-flex px-5 mb-3" style="height: 12px">
		@for($i=0; $i<5; $i++)
		<div class="m-1 bg-{{$i > $quiz->level->index ? 'light' : $quiz->level->color}} rounded h-100 flex-grow-1" style="opacity: {{(4 + $i)/10}}"></div>
		@endfor
	</div>
	@if($quiz->results_count > 2)
	<div>This quiz has so far an average score of <u>{{$quiz->average_score}} out of {{count($quiz->questions)}}</u></div>
		@if($quiz->average_score < count($quiz->questions))
		<div>Can you beat that?😉</div>
		@endif
	@endif
</div>