<section class="discover-recent mb-4" aria-labelledby="{{ $rowHeadingId ?? 'recently-viewed-heading' }}">
	<div class="d-flex align-items-center flex-wrap gap-3 mb-3">
		<h5 id="{{ $rowHeadingId ?? 'recently-viewed-heading' }}" class="mb-0">{{ $rowHeading ?? $row['title'] }}</h5>
		@if($rowSubtitle ?? null)
		<span class="text-muted small">{{ $rowSubtitle }}</span>
		@endif
	</div>
	<div class="discover-recent__rail custom-scroll dragscroll dragscroll-horizontal card-scroll-row">
		<div class="discover-recent__items d-flex gap-3 pb-2">
			@foreach($row['content'] as $piece)
			<a class="discover-recent-card discover-piece-link link-none d-flex align-items-center" href="{{ route('webapp.pieces.show', $piece) }}">
				<img class="discover-recent-card__art" src="{{ $piece->web_image_background }}" alt="" width="72" height="72" loading="lazy">
				<div class="discover-recent-card__copy">
					<p class="{{ ($wrapPieceTitles ?? false) ? 'discover-compact-card__title clamp-2' : 'discover-recent-card__title' }} m-0" title="{{ $piece->name }}"><strong>{{ $piece->name }}</strong></p>
					<p class="discover-recent-card__composer text-muted small m-0">{{ $piece->attribution }}{{ $piece->composer->short_name }}</p>
					@include('webapp.components.piece.level-indicator')
				</div>
				@icon('chevron-right', ['mr' => 0, 'classes' => 'text-muted'])
			</a>
			@endforeach
		</div>
	</div>
</section>
@include('webapp.discover.rows.link-rail-script')
