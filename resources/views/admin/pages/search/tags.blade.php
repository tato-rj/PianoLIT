<form method="GET" action="{{route('admin.api.search')}}" class="p-3">
  <input type="hidden" id="search" name="search" value="{{request('search')}}">
  <input type="hidden" name="lazy-load">
  <div class="d-flex flex-wrap mb-3" id="tags-search">
    @foreach($tags as $tag)
    <span 
      class="tag rounded-pill 
      {{$tag == str_rm(request('search'),'"') ? 'selected-tag random-pill-'.rand(1,4) : 'bg-light'}} 
      px-3 py-1 m-1 cursor-pointer"
      style="-moz-user-select: none; 
          -webkit-user-select: none; 
               -ms-user-select:none; 
                   user-select:none;
                -o-user-select:none;">
      {{$tag}}
    </span>
    @endforeach
  </div>
  <div class="text-center">
    <button class="btn btn-sm btn-default px-3">Find best matches</button>
  </div>
</form>