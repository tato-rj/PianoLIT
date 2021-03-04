<div class="list-group carousel-answers">
@foreach($answers as $answer => $tag)
	<div data-carousel="answer" value="{{$tag}}" data-type="single" style="font-weight: normal;" class="rounded cursor-pointer list-group-item list-group-item-action border-0  mb-1">
		{{intToLetter($loop->index)}}) {{$answer}}
	</div>
@endforeach
</div>