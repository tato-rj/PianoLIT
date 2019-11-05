<div class="cursor-pointer border-0 p-0 mx-1 result-card" style="background: none">
  <div class="d-flex justify-content-between flex-column rounded bg-full text-white py-2 px-3" 
  		style="height: 115px; width: 151px; background: linear-gradient(to right, {{gradient($model->color)[0]}}, {{gradient($model->color)[1]}});">
    <div class="h-100" style="overflow: hidden;">
      <p class="m-0 clamp-2 text-left" style="max-width: 100%;">{{$model->name}}</p>
    </div>
    <div>
      <p class="m-0 text-left"><span><small>{{$model->count}}</small></span></p>
    </div>
  </div>
</div>