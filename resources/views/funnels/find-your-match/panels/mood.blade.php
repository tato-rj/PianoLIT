@php($tags = (new \App\Resources\FindYourMatch\Quiz)->showTags())

@component('funnels.find-your-match.panels.panel', ['loop' => $loop ?? false, 'question' => 'What are you in the mood for?'])

<div class="container-fluid">
	<div class="row carousel-answers">
		<div class="col-12 text-center">
			<div id="dial-label" data-carousel="answer" data-original-text="Turn the dial to start" data-type="single" value="" class="mb-4 bg-white opacity-2 h4">Turn the dial to start</div>
			<input type="text" value="0" id="dial" data-cursor="true" data-tags="{{json_encode($tags)}}">
		</div>
	</div>
</div>

@endcomponent