<div class="cursor-pointer border-0 p-0 mx-1 result-card" style="background: none" data-url="{{route('search.index', ['global', 'search' => $model->name])}}">
  <div class="d-flex justify-content-between flex-column rounded bg-full text-white py-2 px-3" 
  		style="height: 165px; width: 158px; background: linear-gradient(to right, {{gradient($model->color)[0]}}, {{gradient($model->color)[1]}});">
    <div class="h-100" style="overflow: hidden;">
      <h5 class="mb-0 mt-2 clamp-2 text-left" style="max-width: 100%;"><strong>{{$model->name}}</strong></h5>
    </div>
    <div>
      <p class="m-0 text-left"><span><small><strong>{{$model->pieces_count}} {{str_plural('piece', $model->pieces_count)}}</strong></small></span></p>
    </div>
  </div>
</div>