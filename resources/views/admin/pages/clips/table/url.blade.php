<div class="c-clip cursor-pointer" data-clipboard-text="{{route('clips.show', $item)}}">
	@icon('copy', ['color' => 'grey'])<span data-bs-toggle="tooltip" data-bs-placement="top" title="Copied!">{{$item->url}}</span>
</div>