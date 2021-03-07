@php($pieces = (new \App\Resources\FindYourMatch\Quiz)->showPieces())

@component('funnels.find-your-match.panels.panel', ['loop' => $loop ?? false, 'question' => 'Pick the 3 pieces you like the most'])

<div class="list-group carousel-answers">
@foreach($pieces as $piece)
	<div data-carousel="answer" value="{{$piece->id}}" data-type="multi" style="font-weight: normal;" class="rounded cursor-pointer list-group-item list-group-item-action border-0 mb-1">
		<div class="d-flex d-apart w-100">
			<div class="d-flex align-items-center">
				<div class="mr-2">
					<img src="{{$piece->composer->cover_image}}" class="rounded-circle" style="height: 40px; width: 40px">
				</div>
				<div>
					<div class="clamp-1"><strong>{{$piece->name}}</strong></div>
					<div class="opacity-6 clamp-1"><i>by {{$piece->composer->short_name}}</i></div>
				</div>
			</div>
			@include('funnels.find-your-match.components.play')
		</div>
	</div>
@endforeach
</div>
{{-- <div class="container-fluid">
	<div class="carousel-answers row"> 
		@foreach($pieces as $piece)
		@include('funnels.find-your-match.components.play')
		@endforeach
	</div>
</div> --}}

@endcomponent