<section id="quiz">
	@foreach($quiz as $question => $answers)
		@component('funnels.find-your-match.components.question', ['question' => $question, 'loop' => $loop])
			@foreach($answers as $answer)
				@include('funnels.find-your-match.components.answer', [
					'label' => $answer['label'], 
					'subtitle' => $answer['subtitle'] ?? null, 
					'audio' => $answer['audio'] ?? null,
					'keywords' => $answer['keywords']])
			@endforeach
		@endcomponent
	@endforeach
</section>