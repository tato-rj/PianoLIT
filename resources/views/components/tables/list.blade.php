<div class="d-flex">
	<div class="mr-3">
	  @foreach($content as $key => $value)
	  <p class="mb-2"><strong class="text-{{$color ?? 'blue'}}">{!! $key !!}</strong></p>
	  @endforeach
	</div>
	<div>
	  @foreach($content as $key => $value)
	  <p class="mb-2">{!! $value ?? '-' !!}</p>
	  @endforeach
	</div>
</div>