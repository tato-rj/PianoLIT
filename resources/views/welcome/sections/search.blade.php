<div class="text-center col-lg-10 col-12 mx-auto mb-6">
	<h1 class="mb-4"><strong>Find music that inspires you.</strong></h1>
	<p class="text-muted mb-6">Where pianists discover new pieces and find inspiration to play only what they love.</p>

	<input type="text" name="search" class="text-center border-0 p-2 text-lead rounded bg-light w-100 mb-4" 
			style="font-size: 2em;" placeholder="I'm looking for..." data-url="{{route('api.blog.search')}}">

	<div class="text-muted">
		<div class="mb-2">Search for something, let's see what you can find &#10548;</div>
		<div>Ex: <i><u>pieces for the left hand</u></i>, <i><u>flashy intermediate repertoire</u></i>, <i><u>4-hands for beginners</u></i>, etc...</div>
	</div>
</div>

<div class="col-12 mb-7">
    @foreach($collections as $playlist)
      @if(! empty($playlist))
      @component('admin.components.swiper', ['title' => $playlist['title']])
        @foreach($playlist['content'] as $model)
          <form name="{{$model->type == 'piece' ? 'piece_'.snake_case($model->id) : snake_case($model->name)}}_form" method="{{$model->type == 'piece' ? 'POST' : 'GET'}}" action="{{$model->source}}" target="_blank">
            <input type="hidden" name="search" value="{{$model->type == 'piece' ? $model->id : $model->name}}">
            <input type="hidden" name="global">
            <input type="hidden" name="api">
            <input type="hidden" name="discover">
            @include('admin.pages.discover.card')
          </form>
        @endforeach
      @endcomponent
      @endif
    @endforeach
</div>