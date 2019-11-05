<div class="text-center col-lg-10 col-12 mx-auto mb-6">
	<h1 class="mb-4"><strong>Find music that inspires you.</strong></h1>
	<p class="text-muted mb-6">Where pianists discover new pieces and find inspiration to play only what they love.</p>

  <form method="GET" action="{{route('search')}}">
    <input type="hidden" name="global">
    <div class="position-relative mb-3">
      <input type="text" name="search" class="text-center border-0 py-2 px-5 text-lead rounded bg-light w-100" 
      style="font-size: 2em;" 
      placeholder="Try searching for something here...">
      <div class="position-absolute h-100 p-1" style="top: 0; right: 0;">
        <button type="submit" class="btn bg-light bg-white h-100"><i class="fas fa-arrow-right"></i></button>
      </div>
    </div>
  </form>

	<div class="text-muted">
		{{-- <div class="mb-2">Search for something, let's see what you can find &#10548;</div> --}}
		<div>Ex: <i><u>pieces for the left hand</u></i>, <i><u>flashy intermediate repertoire</u></i>, <i><u>4-hands for beginners</u></i>, etc...</div>
	</div>
</div>

<div class="col-12 mb-7">
    @foreach($collections as $playlist)
      @if(! empty($playlist))
      @component('components.swiper', ['title' => $playlist['title']])
        @foreach($playlist['content'] as $model)
          @include('components.cards.galleries.' . $playlist['type'])
        @endforeach
      @endcomponent
      @endif
    @endforeach
</div>