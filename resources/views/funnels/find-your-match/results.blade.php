@component('components.modal', [
	'id' => $modalId ?? 'match-modal',
	'data' => ['piece-id' => $piece->id],
	'options' => [
	    'header' => ['show' => false],
		'body' => ['padding' => 0],
	    'footer' => ['raw' => true],
]])
@slot('body')
	<div class="rounded-top bg-align-center position-relative" style="background-image: url({{$piece->image_background ?? $piece->period->cover_image}}); height: 200px;">
      <button class="close text-white absolute-top-right" type="button" data-bs-dismiss="modal" aria-label="Close result">
          @icon('close', ['mr' => 0])
        </button>

		<img src="{{$piece->composer->cover_image}}" class="rounded-circle position-absolute shadow border border-white border-2x" style="width: 100px; bottom: -50px; left: 25px">
	</div>
	<div class="p-4">
		<div class="text-end">
			<h5 id="{{ $modalId ?? 'match-modal' }}-title" class="mb-0" style="padding-left: 108px"><strong>{{$piece->medium_name}}</strong></h5>
			<p class="text-muted">by {{$piece->composer->name}}</p>
		</div>

		<div class="mb-4">
			<h6 class="mb-2">What's this piece like?</h6>
			<div style="white-space: pre-wrap;">{{$piece->description}}</div>
		</div>

		<div>
			<div class="mb-4 video-container">
				@php
					$video = $piece->tutorials->first();
					if (isset($previewSeconds)) {
						$video = $piece->tutorials->first(function ($tutorial) {
							return strtolower($tutorial->type) === 'performance';
							}) ?? $video;
					}
				@endphp
				@if($video)
				<video class="w-100" id="piece-video-{{$video->id}}" controls playsinline preload="metadata" @isset($previewSeconds) data-result-media @endisset>
					<source src="{{$video->video_url}}" type="video/mp4">
				</video>
				@elseif($piece->audio_path)
				<audio class="w-100" controls preload="metadata" src="{{ storage($piece->audio_path) }}" @isset($previewSeconds) data-result-media @endisset></audio>
				@endif
				@isset($previewSeconds)
				<p class="small text-muted mt-2">A short preview of your match.</p>
				@endisset
			</div>
			<div class="text-center">
				<a href="{{route('webapp.pieces.show', $piece)}}" class="btn rounded-pill btn-primary">Learn more about this piece</a>
			</div>
		</div>
	</div>
@endslot

@slot('footer')
		<div class="text-center p-4 bg-light rounded-bottom">
			<p>Would you like to find more pieces like this one?</p>
			<a href="{{route('webapp.pieces.similar', $piece)}}" class="btn rounded-pill btn-primary-outline">@icon('folder-plus')More like this</a>
		</div>
@endslot
@endcomponent
