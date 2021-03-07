@php($levels = (new \App\Resources\FindYourMatch\Quiz)->showReadingLevels())

@component('funnels.find-your-match.panels.panel', ['loop' => $loop ?? false, 'question' => 'How difficult would it be to sight-read this?'])

<img src="{{asset('images/misc/score1.png')}}" class="w-100">

<div class="my-4">
	<div class="list-group carousel-answers flex-row bg-light mb-2">
	@foreach($levels as $level)
		<div data-carousel="answer" value="{{$level}}" data-type="single" style="font-weight: normal;" class="w-100 cursor-pointer list-group-item list-group-item-action border-0 text-center">{{$loop->iteration}}</div>
	@endforeach
	</div>
	<div class="d-flex d-apart px-2">
		<div class="text-muted"><i>Easiest</i></div>
		<div class="flex-grow bg-grey opacity-2 mx-3" style="height: 1px"></div>
		<div class="text-muted"><i>Hardest</i></div>
	</div>
</div>
{{-- @include('funnels.find-your-match.components.answers', [
	'answers' => [
		'I\'ve no idea how to read this' => 'elementary',
		'It looks hard, it would take me a little while' => 'beginner',
		'Not too difficult, but not too easy either' => 'beginner',
		'This looks pretty simple' => 'intermediate',
	]
]) --}}

@endcomponent