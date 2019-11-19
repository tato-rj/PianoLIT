<div class="cursor-pointer border-0 p-0 mx-1 result-card" style="background: none" data-url="{{route('search.index', ['global', 'search' => $model->name])}}">
  <div class="py-2" style="width: 151px;">
    <div class="h-100 px-3" style="overflow: hidden;">
    	<img src="{{$model->cover_image}}" class="rounded-circle shadow mb-2 mx-auto d-block w-100">
		<p class="m-0 text-center"><strong>{{$model->short_name}}</strong></p>
      <p class="m-0 text-center text-muted"><small>{{$model->pieces_count}} {{str_plural('piece', $model->pieces_count)}}</small></p>
    </div>
  </div>
</div>