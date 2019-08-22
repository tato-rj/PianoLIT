<div class="text-center mb-5">
	<div class="text-muted"><small>DISCLAIMER</small></div>
	<label>This quiz is <strong>{{$quiz->level->name}}</strong></label>
	<div class="w-100 d-flex px-5" style="height: 12px">
		@for($i=0; $i<5; $i++)
		<div class="m-1 opacity-6 bg-{{$i > $quiz->level->index ? 'light' : $quiz->level->color}} rounded h-100 flex-grow-1"></div>
		@endfor
	</div>
</div>