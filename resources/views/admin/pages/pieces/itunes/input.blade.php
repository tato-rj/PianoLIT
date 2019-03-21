<div class="form-group type-container {{$type ?? ''}} mb-2 itunes-form" style="display: {{$display ?? null}}">

	<input rows="1" class="form-control-sm form-control mb-1" placeholder="iTunes album" name="{{$names[0] ?? null}}" value="{{$album ?? null}}">
	<input rows="1" class="form-control-sm form-control mb-1" placeholder="iTunes artist" name="{{$names[1] ?? null}}" value="{{$artist ?? null}}">
	
	@if(! empty($type) && $type == 'original-type')
	<input rows="1" class="form-control form-control-sm mb-1 itunes-link" placeholder="iTunes link">
	@else
	<div class="input-group input-group-sm mb-1">
		<div class="input-group-prepend">
			<a href="{{$link}}" target="_blank" class="input-group-text no-underline"><i class="text-success fas fa-globe"></i></a>
		</div>
		<input rows="1" class="form-control itunes-link" placeholder="iTunes link" name="{{$names[2]}}" value="{{$link}}">		
	</div>
	@endif

	<a class="align-self-stretch btn btn-sm btn-block btn-danger text-white mr-1 remove-field mb-4">
		<strong>Remove</strong>
	</a>

</div>