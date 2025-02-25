@component('components.modal', [
	'id' => 'match-modal',
	'data' => ['piece-id' => $piece->id],
	'options' => [
	    'header' => ['show' => false],
		'body' => ['padding' => 0],
	    'footer' => ['raw' => true],
]])
@slot('body')
	<div class="rounded-top bg-align-center position-relative" style="background-image: url({{$piece->image_background ?? $piece->period->cover_image}}); height: 200px;">
      <button class="close text-white absolute-top-right" type="button" data-dismiss="modal">
          @fa(['icon' => 'times', 'mr' => 0])
        </button>

		<img src="{{$piece->composer->cover_image}}" class="rounded-circle position-absolute shadow border border-white border-2x" style="width: 100px; bottom: -50px; left: 25px">
	</div>
	<div class="p-4">
		<div class="text-right">
			<h5 class="mb-0" style="padding-left: 108px"><strong>{{$piece->medium_name}}</strong></h5>
			<p class="text-muted">by {{$piece->composer->name}}</p>
		</div>

		<div class="mb-4">
			<h6 class="mb-2">What's this piece like?</h6>
			<div style="white-space: pre-wrap;">{{$piece->description}}</div>		
		</div>

		<div>
			<div class="mb-4 video-container">
				<video class="w-100" id="piece-video-{{$piece->tutorials()->first()->id}}">
					<source src="{{$piece->tutorials()->first()->video_url}}" type="video/mp4">
				</video>
			</div>
			<div class="text-center">
				<a href="{{route('webapp.pieces.show', $piece)}}" class="btn rounded-pill btn-primary">Learn more about this piece</a>
			</div>
		</div>
@endslot

@slot('footer')
		<div class="text-center p-4 bg-light rounded-bottom">
			<p>Would you like to find more pieces like this one?</p>
			<a href="{{route('webapp.pieces.similar', $piece)}}" class="btn rounded-pill btn-primary-outline">@fa(['icon' => 'folder-plus'])More like this</a>
		</div>
	</div>
@endslot
@endcomponent