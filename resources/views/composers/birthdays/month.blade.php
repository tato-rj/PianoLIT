@php($hasBirthdays = $calendar->has($loop->iteration))

<div class="col-lg-3 col-md-4 col-6 p-3">
	<div class="{{$hasBirthdays ? 'shadow-center cursor-pointer' : 'border'}} rounded px-4 py-3 h-100">
		<div class="d-flex d-apart mb-2">
			<div class="badge badge-pill alert-{{$hasBirthdays ? 'blue' : 'grey'}}">{{strtoupper($month)}}</div>
			@if($hasBirthdays)
			<div class="text-blue"><strong>{{$calendar[$loop->iteration]->count()}}</strong></div>
			@endif
		</div>
		@if($hasBirthdays)
		<div class="d-flex flex-wrap">
			@foreach($calendar[$loop->iteration] as $composer)
			<img data-toggle="tooltip" data-placement="bottom" title="{{$composer->name}}" src="{{$composer->cover_image}}" style="width: 40px; {{$loop->first ? null : 'margin-left: -18px;'}}" class="rounded-circle mr-2">
			@endforeach
		</div>
		@endif
	</div>
</div>