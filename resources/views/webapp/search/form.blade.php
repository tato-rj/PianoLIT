<form method="GET" action="{{route('webapp.search.results', ['lazy-load'])}}">
  <div class="input-icon">
    @fa(['icon' => 'search', 'color' => 'grey', 'size' => 'lg'])
    <input type="text" name="search" class="form-control border-bottom p-4 rounded-0 bg-transparent border-grey w-100" style="border: 0;" placeholder="Search here...">
    @fa(['icon' => 'algolia', 'fa_type' => 'b', 'color' => 'grey', 'size' => 'lg', 'title' => 'Powered by Algolia'])
  </div>
</form>