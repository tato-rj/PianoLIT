<div class="text-center">
	<img src="{{$feedback['gif']}}" class="mb-4 w-100 rounded">
	<h5 class="m-0 text-teal">You scored</h5>
	<h2 class="mb-3"><strong>{{$feedback['score']}} out of {{$feedback['total']}}</strong></h2>
	<p class="m-0">{!! $feedback['feedback'] !!}</p>
</div>