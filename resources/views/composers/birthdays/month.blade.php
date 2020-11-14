@php($hasBirthdays = $calendar->has($loop->iteration))

<div class="col-lg-3 col-md-4 col-6 t-2 p-3 {{$hasBirthdays ? 'calendar-month' : null}}" style="user-select: none;">
	<div class="{{$hasBirthdays ? 'shadow-center cursor-pointer' : 'border'}} rounded px-4 py-3 h-100">
		<div class="d-flex d-apart mb-2">
			<div class="badge badge-pill alert-{{$hasBirthdays ? 'blue' : 'grey'}}">{{strtoupper($month)}}</div>
			@if($hasBirthdays)
			<div class="text-blue"><strong>{{$calendar[$loop->iteration]->count()}}</strong></div>
			@endif
		</div>
		@if($hasBirthdays)
		<div class="d-flex flex-wrap composer-list">
			@foreach($calendar[$loop->iteration] as $composer)
			<div class="offset-composer composer-item mb-1 d-flex align-items-center">
				<img data-toggle="tooltip" data-placement="bottom" title="{{$composer->name}}" src="{{$composer->cover_image}}" style="width: 40px; " class="rounded-circle mr-2">
				<div class="composer-info w-100" style="display: none;">
					<div class="d-flex d-apart w-100">
						<div class="clamp-1"><strong>{{$composer->short_name}}</strong></div>
						<div class="text-nowrap">@fa(['icon' => 'calendar-alt', 'color' => 'grey']){{$composer->date_of_birth->format('M d, Y')}}</div>
					</div>
				</div>
			</div>
			@endforeach
		</div>
		@endif
	</div>
</div>