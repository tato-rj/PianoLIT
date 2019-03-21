<div class="d-flex justify-content-between">
  <p class="text-center btn-sm">We found {{$pieces->count()}} {{str_plural('result', $pieces->count())}} out of {{\App\Piece::count()}} pieces</p>
  <form method="POST" action="{{route('admin.api.search')}}" target="_blank">
    @csrf
  	{{-- The api field tells the server to return a json --}}
    <input type="hidden" name="api">
    {{-- The global field indicates a search using the text input --}}
    <input type="hidden" name="global">
    {{-- The search field is the one containing the user input --}}
    <input type="hidden" name="search" value="{{implode(' ', $inputArray)}}">
    <button type="submit" class="btn btn-sm btn-link">See JSON response</button>
  </form>
</div>

@foreach($pieces as $piece)
@include('admin.pages.pieces.row')
@endforeach
