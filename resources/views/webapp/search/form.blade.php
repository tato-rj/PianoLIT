<form method="GET" action="{{route('webapp.search.results', ['lazy-load'])}}" id="search-form">
  <div class="input-icon">
    @icon('search', ['color' => 'grey', 'size' => 'lg'])
    <input type="text" name="search" value="{{request('search')}}" class="form-control pianolit-input w-100" aria-label="Search repertoire" placeholder="Search here...">
    <div data-erase="search" class="input-erase position-absolute cursor-pointer p-1 px-2 text-dark" style="display: none;">&times;</div>
    @icon('brand-algolia', ['color' => 'grey', 'size' => 'lg', 'title' => 'Powered by Algolia'])
  </div>
</form>

<div id="most-recent" class="text-center mt-1" style="display: none;">
	<p class="mb-1 text-muted"><small>Most recent searches...</small></p>
	<div class="d-flex flex-wrap justify-content-center"></div>
</div>
