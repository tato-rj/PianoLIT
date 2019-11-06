<form method="GET" action="{{route('search.index')}}">
  <input type="hidden" name="global">
  <div class="position-relative mb-3">
    <input type="text" name="search" class="{{empty($large) ? 'pl-2' : 'pl-5 text-center'}} border-0 py-2 pr-5 text-lead rounded bg-light w-100" 
    style="font-size: {{empty($large) ? null : '2em'}};" 
    placeholder="Try searching for something here..."
    value="{{$value ?? null}}">
    <div class="position-absolute h-100 p-1" style="top: 0; right: 0;">
      <button type="submit" class="btn bg-light bg-white h-100 d-flex flex-center"><i class="fas fa-arrow-right"></i></button>
    </div>
  </div>
</form>