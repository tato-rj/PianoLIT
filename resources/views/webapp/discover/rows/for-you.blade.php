@if(auth('web')->check() && collect($row['content'])->isNotEmpty())
<section class="discover-for-you discover-panel pastel-card rounded mb-4" data-pastel-color="lavender" aria-labelledby="for-you-heading">
	<h5 id="for-you-heading" class="mb-1">{{ $row['title'] }}</h5>
	<p class="text-muted mb-3">Pieces you might like.</p>
	<div class="discover-for-you__rail custom-scroll dragscroll dragscroll-horizontal card-scroll-row">
		<div class="d-flex gap-3 pb-2">
			@foreach(collect($row['content'])->take(4) as $piece)
				@include('webapp.discover.cards.compact-piece', ['showMedia' => true, 'recommendationPosition' => $loop->iteration])
			@endforeach
		</div>
	</div>
    @include('webapp.discover.rows.playing-level')
</section>
@include('webapp.discover.rows.link-rail-script')
@elseif(auth('web')->check() && array_key_exists('playing_level', $row))
    @include('webapp.discover.rows.playing-level')
@endif
