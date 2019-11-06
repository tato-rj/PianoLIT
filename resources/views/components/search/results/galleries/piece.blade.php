<div class="cursor-pointer p-0 mx-1 result-card">
	<div class="d-flex justify-content-between flex-column rounded bg-white border py-2 px-3" 
	style="height: 188px; width: {{empty($width) ? '188px' : '100%'}};">
		<div class="h-100" style="overflow: hidden;">
			<span class="badge badge-pill mb-2 bg-{{$model->level->name}}">{{$model->level->name}}</span>
			<p class="mb-0 clamp-2" style="max-width: 100%;"><strong>{{$model->name}}</strong></p>
			<p class="clamp-1 m-0 text-muted"><small>by {{$model->composer->short_name}}</small></p>
		</div>
		<div>
			<a href="" class="btn btn-xs btn-block btn-light border">LEARN MORE</a>
		</div>
	</div>
</div>
