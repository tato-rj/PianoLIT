<div>
	@if($item->isFake())
	@icon('contact-round', ['color' => 'muted'])@if($item->reviewer){{ $item->reviewer }}@else<i>Anonymous</i>@endif
	@else
	@icon('user', ['color' => 'primary']){{$item->user()->exists() ? $item->user->full_name : $item->reviewer}}
	@endif
</div>
