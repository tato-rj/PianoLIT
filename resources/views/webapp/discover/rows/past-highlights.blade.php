@php($pastHighlights = collect($row['content']))
@if($pastHighlights->isNotEmpty())
<section class="discover-composer-feature discover-past-highlights mb-4" aria-labelledby="past-highlights-heading">
	<div class="d-flex d-apart mb-3">
		<h5 id="past-highlights-heading" class="m-0">Past highlights</h5>
		<a href="{{ route('webapp.highlights') }}" class="btn-raw link-primary d-inline-flex align-items-center gap-2">View all @icon('arrow-right', ['mr' => 0])</a>
	</div>
	<div class="discover-composer-feature__layout">
		@include('webapp.components.piece.highlight', ['piece' => $pastHighlights->first(), 'freePick' => true])
		@if($pastHighlights->count() > 1)
		<div class="discover-composer-feature__pieces custom-scroll" role="region" aria-label="Earlier highlights" tabindex="0">
			@foreach($pastHighlights->slice(1) as $piece)
				@include('webapp.discover.cards.compact-piece', ['showMedia' => false])
			@endforeach
		</div>
		@endif
	</div>
</section>
@endif
