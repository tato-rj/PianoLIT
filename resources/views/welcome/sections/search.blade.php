<div class="row">
  <div class="text-center col-lg-8 col-md-10 col-12 mx-auto mb-6">
  	<h1 class="mb-4"><strong>Find music that inspires you.</strong></h1>
  	<p class="text-muted mb-6">Where pianists discover new pieces and find inspiration to play only what they love.</p>

    @include('components.search.form', ['large' => true])

  	<div class="text-muted">
  		{{-- <div class="mb-2">Search for something, let's see what you can find &#10548;</div> --}}
  		<div>Ex: <i><u class="search-suggestion cursor-pointer">pieces for the left hand</u></i>, <i><u class="search-suggestion cursor-pointer">flashy intermediate repertoire</u></i>, <i><u class="search-suggestion cursor-pointer">4-hands for beginners</u></i>, etc...</div>
  	</div>
  </div>
</div>
<div class="row">
  <div class="col-lg-10 col-12 mx-auto mb-7">
    @foreach($collections as $playlist)
      
      @include('components.search.results.row')

      @if($loop->last)
      <div class="col-12 text-center" id="show-more">
        <p class="text-grey">Did you like these recommendations? Click below to show more</p>
        <button class="btn btn-wide btn-grey-outline" data-url="{{route('search.more')}}">SHOW MORE</button>
      </div>
      @endif

    @endforeach
  </div>
</div>