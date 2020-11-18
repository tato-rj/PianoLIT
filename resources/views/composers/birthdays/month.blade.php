@php($hasBirthdays = $calendar->has($loop->iteration))

<div class="col-lg-3 col-md-4 col-6 t-2 p-3 {{$hasBirthdays ? 'calendar-month' : null}}" style="user-select: none; display: none;">
	<div class="{{$hasBirthdays ? 'shadow-center cursor-pointer' : 'border'}} rounded px-4 py-3 h-100">
		<div class="d-flex d-apart mb-3">
			<div class="badge badge-pill 
			@if(now()->month == $loop->iteration)
			alert-green
			@else
			alert-{{$hasBirthdays ? 'blue' : 'grey'}}
			@endif
			">{{strtoupper($month)}}</div>
			@if($hasBirthdays)
			<div class="text-blue"><strong>@fa(['icon' => 'birthday-cake', 'color' => 'grey']){{$calendar[$loop->iteration]->count()}}</strong></div>
			@endif
		</div>
		@if($hasBirthdays)
		<div class="d-flex flex-wrap composer-list offset-list">
			@foreach($calendar[$loop->iteration] as $composer)
				@include('composers.birthdays.composer')
			@endforeach
		</div>
		@endif
	</div>
</div>