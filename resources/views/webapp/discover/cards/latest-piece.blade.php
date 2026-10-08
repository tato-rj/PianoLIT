<a class="discover-latest-card discover-piece-link link-none" href="{{ route('webapp.pieces.show', $piece) }}">
	<img class="discover-latest-card__art" src="{{ $piece->cover_path ? storage($piece->cover_path) : asset(optional($piece->period)->cover_image ?: 'images/webapp/thumbnail.jpg') }}" alt="" width="600" height="280" loading="lazy">
	<div class="discover-latest-card__copy">
		<p class="discover-latest-card__title clamp-2 mb-1" title="{{ $piece->name }}"><strong>{{ $piece->name }}</strong></p>
		<p class="text-muted small mb-2">{{ $piece->attribution }}{{ $piece->composer->short_name }}</p>
		@if($piece->extended_level_name)
		<p class="d-flex align-items-center small mb-3">
			@icon('circle', ['mr' => 2, 'classes' => 'color-' . $piece->level_name, 'filled' => true])
			<span>{{ ucfirst($piece->extended_level_name) }}</span>
		</p>
		@endif
		@include('webapp.components.piece.media-icons')
	</div>
</a>
