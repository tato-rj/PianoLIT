<a class="discover-compact-card discover-piece-link link-none d-flex align-items-center" href="{{ route('webapp.pieces.show', $piece) }}">
	<img class="discover-compact-card__art" src="{{ $piece->web_image_background }}" alt="" width="80" height="80" loading="lazy">
	<div class="discover-compact-card__copy">
		<p class="discover-compact-card__title clamp-1 mb-0" title="{{ $piece->name }}"><strong>{{ $piece->name }}</strong></p>
		<p class="text-muted small m-0">{{ $piece->attribution }}{{ $piece->composer->short_name }}</p>
		@include('webapp.components.piece.level-indicator')
		@if($showMedia ?? false)
		<div class="discover-compact-card__media small mt-2">
			@include('webapp.components.piece.media-icons', ['compactMedia' => true])
		</div>
		@endif
	</div>
	@icon('chevron-right', ['mr' => 0, 'classes' => 'text-muted'])
</a>
