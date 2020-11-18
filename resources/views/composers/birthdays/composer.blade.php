<div class="composer-item mb-2 d-flex align-items-center rounded-pill pr-2">
	<img data-toggle="tooltip" data-placement="bottom" title="{{$composer->name}}" src="{{$composer->cover_image}}" style="width: 36px; " class="rounded-circle mr-2">
	<div class="composer-info w-100" style="display: none;">
		<div class="d-flex d-apart w-100">
			<div class="clamp-1"><strong>{{$composer->short_name}}</strong></div>
			<div class="text-nowrap text-muted">{{$composer->upcoming_birthday->shortEnglishDayOfWeek}} <strong>{{$composer->upcoming_birthday->format('jS')}}</strong></div>
		</div>
	</div>
</div>