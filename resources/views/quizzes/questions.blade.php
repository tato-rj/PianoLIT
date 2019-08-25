@php($list = ['a', 'b', 'c', 'd'])
@foreach($quiz->questions() as $question)
<div class="mb-5 position-relative quiz-items">
	<div class="question-overlay" id="overlay-{{$loop->iteration}}"></div>	
	<div class="quiz-question mb-4">
		<label class="text-muted"><small>Question {{$loop->iteration}} of {{$loop->count}}</small></label>
		<h5 style="line-height: 1.8;"><span class="bg-primary text-white rounded px-2 py-1 mr-2"><strong>Q</strong></span>{{$question['Q']}}</h5>
		@if($question['audio'])
		<audio controls class="mt-4 w-100 d-block audio">
			<source src="{{asset($question['audio'])}}" type="audio/mp3">
		</audio>
		@endif
		@if($question['image'])
			<img src="{{asset($question['image'])}}" class="mt-3 w-100" style="max-width: 360px">
		@endif
	</div>
	<div class="quiz-answers">
		<div class="list-group">
			@foreach($question['A'] as $answer)
				<button type="button" class="list-group-item list-group-item-action border-0 rounded-pill text-left mb-1 d-flex justify-content-between align-items-center" {{strhas($answer, '[x]') ? 'correct' : null}}
						data-overlay="#overlay-{{$loop->parent->iteration}}">
					{{$list[$loop->index]}}) {{str_replace('[x]', '', $answer)}}
					<i class="fas fa-{{strhas($answer, '[x]') ? 'check' : 'times'}}-circle" style="display: none;"></i>
				</button>
			@endforeach
		</div>
	</div>
</div>
@endforeach