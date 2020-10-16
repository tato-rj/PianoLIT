<div class="col-lg-4 col-md-4 col-10 mx-auto p-2">
	<div class="bg-{{$color ?? null}} {{! empty($border) ? 'border' : null}} rounded px-4 py-3">
		{{$button}}

		@isset($list)
		{{$list}}
		@else
		<ul class="list-flat li-mb-2">
			@foreach($items as $item)
			<li>@fa(['color' => 'green', 'icon' => 'check', 'if' => $loop->iteration > 2])<strong>{!! $item !!}</strong></li>
			@endforeach
		</ul>
		@endisset
	</div>
</div>