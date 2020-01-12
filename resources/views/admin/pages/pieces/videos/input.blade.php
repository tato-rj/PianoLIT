<div class="form-group type-container {{$type ?? ''}} mb-2 videos-form" style="display: {{$display ?? null}}">
	<div class="d-flex d-apart mb-2 quick-fill">
		<div class="btn btn-sm btn-outline-secondary cursor-pointer default-performance" style="display: none;" data-title="Performance" data-description="Watch a video recording of this piece">Default performance</div>
		<div>
			<select class="form-control form-control-sm" data-name="tutorial-description">
				<option selected disabled>Common tutorials</option>
				<option value='Softening the edges of your phrases and creating that "arc-shape" sound'>Arc-shape sound</option>
				<option value='Using your arm and the concept of "free-fall" to create a consistent solid sound'>Free-fall</option>
				<option value='How to balance the sound between your hands'>Hands balance</option>
				<option value="The importance of controlling the sound of your thumb">Thumb control</option>
				<option value="How to group notes into phrases and shape your lines with dynamic control">Dynamics and phrasing</option>
			</select>
		</div>
	</div>

	<input rows="1" class="form-control-sm form-control mb-1 video-title" placeholder="Title" name="{{$names[0] ?? null}}" value="{{$title ?? null}}">
	<input rows="1" class="form-control-sm form-control mb-1 video-description" placeholder="Description" name="{{$names[1] ?? null}}" value="{{$description ?? null}}">
	
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