@component('webapp.explore.rows.row', ['data' => $row])
<div class="custom-scroll dragscroll dragscroll-horizontal">
	<div class="d-flex pb-2">
		@foreach($row['collection'] as $period)
		<div class="cursor-pointer mr-3 text-center search-card" data-url="{{route('webapp.search.results', ['search' => $period->name])}}">
			<img src="https://picsum.photos/200/200?random={{$loop->iteration}}" class="rounded-circle mb-2" style="width: 114px">
			<p class="m-0 clamp-2" style="line-height: 1"><small><strong>{{ucfirst($period->name)}}</strong></small></p>
			<p class="text-muted m-0"><small>{{$period->pieces_count}} pieces</small></p>
		</div>
		@endforeach
	</div>
</div>
@endcomponent
