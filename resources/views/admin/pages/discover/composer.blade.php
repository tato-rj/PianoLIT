<div class="cursor-pointer border-0 p-0 mx-1 mr-2 result-card" style="background: none">
  <div class="d-flex rounded-circle justify-content-between flex-column bg-full text-white py-2 px-3 mb-1" 
  		style="height: 115px; background-image: url({{$model->background}}); background-size: cover; width: 115px;"> </div>
    <div class="h-100 text-center" style="overflow: hidden; line-height: 1">
      <p class="m-0 clamp-2" style="max-width: 100%;"><small><strong>{{$model->name}}</strong></small></p>
      <p class="m-0"><span><small>{{$model->pieces_count}} pieces</small></span></p>
    </div>
</div>