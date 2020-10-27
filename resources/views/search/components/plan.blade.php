<div class="col-lg-6 col-md-6 col-12 p-2">
	<div class="bg-{{$color ?? null}} {{! empty($border) ? 'border' : null}} rounded px-4 py-3">
		{{$button}}

		@isset($list)
		{{$list}}
		@else
		<ul class="list-flat li-mb-2">
			@foreach($items as $item)
			<li>@fa(['color' => 'green', 'icon' => 'check'])<strong>{!! $item !!}</strong></li>
			@endforeach
		</ul>
		@endisset
	</div>
</div>