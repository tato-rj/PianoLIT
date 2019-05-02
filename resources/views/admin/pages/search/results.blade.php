<div class="d-flex justify-content-between">
  <p class="text-center btn-sm">We found {{$pieces->count()}} {{str_plural('result', $pieces->count())}} out of {{\App\Piece::count()}} pieces</p>
  <form method="GET" action="{{route('admin.api.search')}}" target="_blank">
  	{{-- The api field tells the server to return a json --}}
    <input type="hidden" name="api">
    {{-- The global field indicates a search using the text input --}}
    <input type="hidden" name="global">
    {{-- The search field is the one containing the user input --}}
    <input type="hidden" name="search" value="{{implode(' ', $inputArray)}}">
    <button type="submit" class="btn btn-sm btn-link">See JSON response</button>
  </form>
</div>

<div class="list-group list-group-flush mx-2">
  @foreach($pieces as $piece)
  <a href="{{route('admin.pieces.edit', $piece->id)}}" title="Click to edit" class="list-group-item list-group-item-action">{{$piece->LongName}}</a>
  @endforeach
</div>
