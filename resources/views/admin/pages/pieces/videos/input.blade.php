<div class="form-group type-container {{$type ?? 'd-flex'}} mb-2" style="display: {{$display ?? null}}">

	<a class="align-self-stretch btn btn-sm btn-danger text-white mr-1 remove-field">
		<i class="fas fa-minus"></i>
	</a>

	@if(! empty($type) && $type == 'original-type')
	<div class="input-group input-group-sm">
	<input rows="1" class="form-control-sm form-control">
{{-- 		<div class="input-group-append">
			<button class="input-group-text text-warning bg-muted videos-to-mp3"><i class="fas fa-file-download"></i></button>
		</div> --}}
	</div>
	@else
	<div class="input-group input-group-sm">
		<div class="input-group-prepend">
			<a href="{{$piece->cloudUrlFor($value)}}" target="_blank" class="input-group-text no-underline"><i class="text-success fas fa-globe"></i></a>
		</div>
		<input rows="1" class="form-control" name="{{$name}}" value="{{$value}}">
{{-- 		<div class="input-group-append">
			<button class="input-group-text text-warning bg-muted videos-to-mp3"><i class="fas fa-file-download"></i></button>
		</div> --}}
	</div>
	@endif

</div>