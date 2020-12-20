<div>
	@if($item->isFake())
	@fa(['icon' => 'user-secret', 'color' => 'muted']){!! $item->reviewer ?? '<i>Anonymous</i>' !!}
	@else
	@fa(['icon' => 'user', 'color' => 'primary']){{$item->user()->exists() ? $item->user->full_name : $item->reviewer}}
	@endif
</div>