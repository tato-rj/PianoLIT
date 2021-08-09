<div class="form-group type-container {{$type ?? ''}} mb-2 videos-form" style="display: {{$display ?? null}}">

	@if(! empty($type) && $type == 'original-type')
	<select class="form-control form-control-sm mb-1" data-name="type">
		<option selected disabled>Types</option>
		<option data-type="Performance" data-category="performance" data-description="Watch a video recording of this piece">Performance</option>
		<option data-type="Slow performance" data-category="slow" data-description="Learn with a slow video recording">Slow performance</option>
		<option data-type="Tutorial" data-category="tutorial">Tutorial</option>
		<option data-type="Synthesia" data-category="synthesia" data-description="Follow along one note at a time">Synthesia</option>
		<option data-type="Harmonic analysis" data-category="harmony" data-description="A full harmonic analysis one measure at a time">Harmonic analysis</option>
	</select>
	<input class="video-type" hidden>
	<input class="video-category" hidden>
	<input rows="1" class="form-control-sm form-control mb-1 video-description" placeholder="Description">
	<input rows="1" class="form-control form-control-sm mb-1 videos-link" placeholder="File name">

	@else
	<input type="hidden" name="videos[{{$loop->index}}][id]" value="{{$tutorial->id}}">
	<select class="form-control form-control-sm mb-1" data-name="type">
		<option selected disabled>Types</option>
		<option data-type="Performance" data-category="performance" {{$tutorial->category == 'performance' ? 'selected' : null}} data-description="Watch a video recording of this piece">Performance</option>
		<option data-type="Slow performance" data-category="slow" {{$tutorial->category == 'slow' ? 'selected' : null}} data-description="Learn with a slow video recording">Slow performance</option>
		<option data-type="Tutorial" data-category="tutorial" {{$tutorial->category == 'tutorial' ? 'selected' : null}}>Tutorial</option>
		<option data-type="Synthesia" data-category="synthesia" {{$tutorial->category == 'synthesia' ? 'selected' : null}} data-description="Follow along with an animated video">Synthesia</option>
		<option data-type="Harmonic analysis" data-category="harmony" {{$tutorial->category == 'harmony' ? 'selected' : null}} data-description="A full harmonic analysis one measure at a time">Harmonic analysis</option>
	</select>
	<input class="video-type" name="{{'videos['.$loop->index.'][type]'}}" value="{{$tutorial->type}}" hidden>
	<input class="video-category" name="{{'videos['.$loop->index.'][category]'}}" value="{{$tutorial->category}}" hidden>
	<input rows="1" class="form-control-sm form-control mb-1 video-description" placeholder="Description" name="{{'videos['.$loop->index.'][description]'}}" value="{{$tutorial->description}}">
	<div class="input-group input-group-sm mb-1">
		<div class="input-group-prepend">
			<a href="{{$tutorial->url}}" target="_blank" class="input-group-text no-underline"><i class="text-success fas fa-globe"></i></a>
		</div>
		<input rows="1" class="form-control videos-link" placeholder="File name" name="{{'videos['.$loop->index.'][filename]'}}" value="{{$tutorial->filename}}">		
	</div>
	@endif

	<a class="align-self-stretch btn btn-sm btn-block btn-danger text-white mr-1 remove-field mb-4">
		<strong>Remove</strong>
	</a>

</div>