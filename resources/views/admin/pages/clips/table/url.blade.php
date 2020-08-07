<div class="c-clip cursor-pointer" data-clipboard-text="{{route('clips.show', $item)}}">
	@fa(['icon' => 'copy', 'color' => 'grey'])<span data-toggle="tooltip" data-placement="top" title="Copied!">{{$item->url}}</span>
</div>