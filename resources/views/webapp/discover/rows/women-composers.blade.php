@php($featuredComposer = optional(collect($row['content'])->first())->composer)
@if($featuredComposer)
<section class="discover-women discover-panel rounded mb-4" aria-labelledby="women-composers-heading">
	<h5 id="women-composers-heading" class="mb-3">{{ $row['title'] }}</h5>
	<div class="discover-women__layout">
		<div class="discover-women__feature">
			<a class="discover-women__portrait-link" href="{{ route('webapp.composers.show', $featuredComposer) }}" aria-label="About {{ $featuredComposer->name }}">
				<img class="discover-women__portrait" src="{{ $featuredComposer->cover_image ?: asset('images/misc/placeholder-image.png') }}" alt="Portrait of {{ $featuredComposer->name }}" width="160" height="160" loading="lazy">
			</a>
			<div class="discover-women__biography">
				<h6 class="mb-2">{{ $featuredComposer->name }}</h6>
				<p class="text-muted small mb-0">{{ \Illuminate\Support\Str::limit(trim(strip_tags($featuredComposer->biography ?: $featuredComposer->curiosity ?: 'Explore this composer’s piano repertoire.')), 180) }}</p>
			</div>
			<a href="{{ route('webapp.search.results', ['search' => $featuredComposer->name, 'model' => \App\Composer::class]) }}" class="discover-women__action btn btn-primary d-inline-flex align-items-center justify-content-center gap-3">Explore pieces @icon('arrow-right', ['mr' => 0])</a>
		</div>
		<div class="discover-women__pieces custom-scroll" role="region" aria-label="Pieces from women composers" tabindex="0">
			@foreach($row['content'] as $piece)
				@include('webapp.discover.cards.compact-piece', ['showMedia' => false])
			@endforeach
		</div>
	</div>
</section>
@endif
