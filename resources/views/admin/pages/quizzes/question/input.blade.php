<div class="form-group type-container {{$type ?? ''}} mb-2 quiz-form" style="display: {{$display ?? null}}">

	@for($i=0; $i<5; $i++)
		@if($i == 0)
		<input rows="1" class="form-control-sm form-control mb-1" placeholder="Question" name="{{$names[$i] ?? null}}" value="{{$question ?? null}}">
		@else
		<input rows="1" class="form-control-sm form-control mb-1" placeholder="Answer {{$i}}" name="{{$names[$i] ?? null}}" value="{{$answers[$i-1] ?? null}}">
		@endif
	@endfor

	<a class="align-self-stretch btn btn-sm btn-block btn-danger text-white mr-1 remove-field mb-4">
		<strong>Remove</strong>
	</a>

</div>