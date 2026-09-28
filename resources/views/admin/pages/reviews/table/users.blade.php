<div>
	@if($item->isFake())
	@icon('contact-round', ['color' => 'muted']){!! $item->reviewer ?? '<i>Anonymous</i>' !!}
	@else
	@icon('user', ['color' => 'primary']){{$item->user()->exists() ? $item->user->full_name : $item->reviewer}}
	@endif
</div>