<div class="mb-4">
	<h5>{{$row['title']}}</h5>
	<div class="custom-scroll dragscroll dragscroll-horizontal">
		<div class="d-flex pb-2" style="height: 124px;">
			@foreach($row['content'] as $card)
			@include('webapp.discover.rows.card', compact('row'))
			@endforeach
		</div>
	</div>
</div>