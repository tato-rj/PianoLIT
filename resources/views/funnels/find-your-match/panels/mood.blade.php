@component('funnels.find-your-match.panels.panel', ['loop' => $loop ?? false, 'question' => 'What are you in the mood for?'])

<div class="container-fluid">
	<div class="row carousel-answers">
		<div class="col-12 text-center">
			<div id="dial-label" data-carousel="answer" data-type="single" value="" class="mb-4 bg-white font-weight-old opacity-2 h4">Turn the dial to start</div>
			<input type="text" value="0" id="dial" data-cursor="true" data-tags="{{json_encode($tags)}}">
		</div>
{{-- 	@foreach($tags as $button)
	<div class="col-lg-4 col-md-4 col-6 p-1">
		<div data-carousel="answer" data-type="multi" value="{{$button}}" class="py-3 rounded cursor-pointer w-100 list-group-item list-group-item-action border-0">
			@fa(['icon' => 'tag']){{$button}}
		</div>
	</div>
	@endforeach --}}
	</div>
</div>

@endcomponent