<div class="d-flex justify-content-between">
  {{-- <p class="text-center btn-sm">We found a total of <strong>{{$total}} {{str_plural('result', $total)}}</strong> out of {{\App\Piece::count()}} pieces</p> --}}
  <form method="GET" action="{{route('admin.api.search')}}" target="_blank">
    <input type="hidden" name="api">
    <input type="hidden" name="search" value="{{request('search')}}">
    <button type="submit" class="btn btn-sm btn-link">See JSON response</button>
  </form>
</div>
<div class="text-center alert-warning rounded p-2 mb-3">
  <small><i class="fas fa-stopwatch mr-1"></i>This query took <strong>{{ number_format((microtime(true) - LARAVEL_START), 1) }}</strong> seconds to load</small>
</div>
<div class="list-group">
    @include('admin.pages.search.result-rows')
</div>
@if(request()->has('lazy-load'))
<button class="btn btn-block btn-outline-secondary mt-3" id="load-more" 
  data-url="{{route('admin.api.search', [
    'search' => request('search'),
    'lazy-load', 'rendered'
  ])}}">Load more</button>
@endif