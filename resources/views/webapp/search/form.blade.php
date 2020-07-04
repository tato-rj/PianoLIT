<form method="GET" action="{{route('webapp.search.results', ['lazy-load'])}}" id="search-form">
  <div class="input-icon">
    @fa(['icon' => 'search', 'color' => 'grey', 'size' => 'lg'])
    <input type="text" name="search" class="form-control border-bottom p-4 rounded-0 bg-transparent border-grey w-100" style="border: 0;" placeholder="Search here...">
    @fa(['icon' => 'algolia', 'fa_type' => 'b', 'color' => 'grey', 'size' => 'lg', 'title' => 'Powered by Algolia'])
  </div>
</form>

<div id="most-recent" class="text-center mt-1" style="display: none;">
	<p class="mb-1 text-muted"><small>Most recent searches...</small></p>
	<div class="d-flex flex-wrap justify-content-center"></div>
</div>