<div class="d-flex justify-content-between">
  <p class="text-center btn-sm">We found {{$pieces->count()}} {{str_plural('result', $pieces->count())}} out of {{\App\Piece::count()}} pieces</p>
  <form method="GET" action="{{route('admin.api.search')}}" target="_blank">
  	{{-- The api field tells the server to return a json --}}
    <input type="hidden" name="api">
    {{-- The global field indicates a search using the text input --}}
    <input type="hidden" name="global">
    {{-- The search field is the one containing the user input --}}
    <input type="hidden" name="search" value="{{request('search')}}">
    <button type="submit" class="btn btn-sm btn-link">See JSON response</button>
  </form>
</div>
<div class="text-center alert-warning rounded p-2 mb-3">
  <small><i class="fas fa-stopwatch mr-1"></i>This query took <strong>{{ number_format((microtime(true) - LARAVEL_START), 1) }}</strong> seconds to load</small>
</div>
<div class="list-group">
{{--   @foreach($pieces as $piece)
  <a href="{{route('admin.pieces.edit', $piece->id)}}" title="Click to edit" class="py-2 border-0 list-group-item list-group-item-action"><small>
    <span class="badge alert-teal mr-1 badge-pill">{{$loop->iteration}}</span>
    {{$piece->medium_name_with_composer}}
  </small></a>
  @endforeach --}}
</div>
