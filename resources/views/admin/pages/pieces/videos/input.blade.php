<div class="form-group type-container {{$type ?? ''}} mb-2 videos-form" style="display: {{$display ?? null}}">

	<input rows="1" class="form-control-sm form-control mb-1" placeholder="Title" name="{{$names[0] ?? null}}" value="{{$title ?? null}}">
	<input rows="1" class="form-control-sm form-control mb-1" placeholder="Description" name="{{$names[1] ?? null}}" value="{{$description ?? null}}">
	
	@if(! empty($type) && $type == 'original-type')
	<input rows="1" class="form-control form-control-sm mb-1 videos-link" placeholder="File name">
	@else
	<div class="input-group input-group-sm mb-1">
		<div class="input-group-prepend">
			<a href="{{$url}}" target="_blank" class="input-group-text no-underline"><i class="text-success fas fa-globe"></i></a>
		</div>
		<input rows="1" class="form-control videos-link" placeholder="File name" name="{{$names[2]}}" value="{{$filename}}">		
	</div>
	@endif

	<a class="align-self-stretch btn btn-sm btn-block btn-danger text-white mr-1 remove-field mb-4">
		<strong>Remove</strong>
	</a>

</div>