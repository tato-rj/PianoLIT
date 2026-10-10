<div id="empty" class="search-empty text-center" role="status" style="display: none;">
	<img class="search-empty__image" src="{{ asset('images/webapp/empty-search.png') }}" alt="" width="960" height="576">
	<h3 class="search-empty__title">No results found</h3>
	<p class="search-empty__message" data-empty-message></p>
	<p class="search-empty__hint text-muted">Try a different keyword, or explore our library to discover new pieces.</p>
	<div class="search-empty__actions">
		<a href="{{ route('webapp.search.results', ['catalogue' => 1]) }}" class=" ">
			@icon('search') Clear search
		</a>
		<div class="search-empty__destinations">
			<a href="{{ route('webapp.explore') }}" class="btn btn-secondary">
				@icon('music') Explore our library
			</a>
			<a href="{{ route('webapp.playlists') }}" class="btn btn-secondary">
				@icon('layers') Go to Collections
			</a>
		</div>
	</div>
</div>