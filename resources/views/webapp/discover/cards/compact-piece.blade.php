<a class="discover-compact-card discover-piece-link link-none d-flex align-items-center" href="{{ route('webapp.pieces.show', $piece) }}">
	<img class="discover-compact-card__art" src="{{ $piece->cover_path ? storage($piece->cover_path) : asset(optional($piece->period)->cover_image ?: 'images/webapp/thumbnail.jpg') }}" alt="" width="80" height="80" loading="lazy">
	<div class="discover-compact-card__copy">
		<p class="discover-compact-card__title clamp-2 mb-1" title="{{ $piece->name }}"><strong>{{ $piece->name }}</strong></p>
		<p class="text-muted small m-0">{{ $piece->attribution }}{{ $piece->composer->short_name }}</p>
		@if($showMedia ?? false)
		<ul class="discover-compact-card__media list-unstyled d-flex flex-wrap gap-2 text-muted small mt-2 mb-0" aria-label="Available media">
			@if($piece->hasAudio())
			<li class="d-flex align-items-center">@icon('headphones', ['mr' => 1])Audio</li>
			@endif
			@if($piece->hasScore(true))
			<li class="d-flex align-items-center">@icon('file-text', ['mr' => 1])Score</li>
			@endif
		</ul>
		@endif
	</div>
	@icon('chevron-right', ['mr' => 0, 'classes' => 'text-muted'])
</a>
