<div class="d-flex flex-wrap flex-center mb-4">
  <a href="{{route('admin.api.discover')}}" {{url()->current() == route('admin.api.discover') ? 'selected' : null}} class="mx-1 btn btn-nav rounded-0">Discover</a>
  <a href="{{route('admin.api.tour')}}" {{url()->current() == route('admin.api.tour') ? 'selected' : null}} class="mx-1 btn btn-nav rounded-0">Tour</a>
  <a href="{{route('admin.api.search')}}" {{url()->current() == route('admin.api.search') ? 'selected' : null}} class="mx-1 btn btn-nav rounded-0">Search</a>
</div>