@if(auth('web')->check() && collect($row['content'])->isNotEmpty())
<section class="discover-for-you discover-panel rounded mb-4" aria-labelledby="for-you-heading">
	<h5 id="for-you-heading" class="mb-1">{{ $row['title'] }}</h5>
	<p class="text-muted mb-3">Picked for your level and taste.</p>
	<div class="discover-for-you__rail custom-scroll dragscroll dragscroll-horizontal card-scroll-row">
		<div class="d-flex gap-3 pb-2">
			@foreach($row['content'] as $piece)
				@include('webapp.discover.cards.compact-piece', ['showMedia' => true])
			@endforeach
		</div>
	</div>
</section>
@include('webapp.discover.rows.link-rail-script')
@endif
