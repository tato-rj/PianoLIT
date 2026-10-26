<button type="submit" class="btn btn-{{$size ?? null}} btn-{{$theme ?? 'primary'}} {{isset($block) ? 'd-block w-100' : null}}">
	@isset($icon)@icon($icon)@endisset{{$label}}
</button>