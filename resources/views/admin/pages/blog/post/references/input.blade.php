<div class="form-group type-container {{$type ?? ''}} mb-2 reference-form" style="display: {{$display ?? null}}">

	<input rows="1" class="form-control-sm form-control mb-1" placeholder="Reference" name="{{$name ?? null}}" value="{{$reference ?? null}}">

	<a class="align-self-stretch btn btn-sm btn-block btn-danger text-white mr-1 remove-field mb-4">
		<strong>Remove</strong>
	</a>

</div>