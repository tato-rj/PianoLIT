<div class="border-bottom py-2">
	<a href="" class="link-none">
		<div class="mb-2 text-uppercase">@pill(['label' => $piece->extended_level_name, 'color' => $piece->level_name.'-raw'])</div>
		<h6 class="m-0" style="line-height: 1">{{$piece->long_name}}</h6>
		<p>{{$piece->composer->name}}</p>
		<div class="d-flex d-apart">
			<div class="text-muted">
				@fa(['icon' => 'eye', 'color' => 'muted']){{$piece->views_count}} {{ str_plural('view', $piece->views_count) }}
			</div>
			<div>
				@include('webapp.components.favorite')
			</div>
		</div>
	</a>
</div>