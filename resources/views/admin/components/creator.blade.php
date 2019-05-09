<div class="mt-3">
	@if($model->creator()->exists())
	<p class="text-muted"><small><i>This {{$type}} was created by <strong>{{$model->creator->name}}</strong></i></small></p>
	@else
	<p class="text-muted"><small><i>The creator of this {{$type}} has been removed</i></small></p>
	@endif
</div>