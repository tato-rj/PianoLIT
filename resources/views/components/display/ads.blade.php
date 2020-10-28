@php(shuffle($ads))

@foreach($ads as $view)
	@if(view()->exists('components.display.ads.' . $view) && $ad[$view])
	@include('components.display.ads.' . $view)
	@endif
@endforeach
