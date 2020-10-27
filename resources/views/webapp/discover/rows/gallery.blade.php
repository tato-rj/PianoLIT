<div class="mb-4">
	<h5 class="mb-3">{{$row['title']}}</h5>
	<div class="custom-scroll dragscroll dragscroll-horizontal">
		<div class="d-flex pb-2" style="height: 144px;">
			@foreach($row['content'] as $card)
				@include('webapp.discover.cards.' . $row['type'], compact('hasFullAccess'))
			@endforeach
		</div>
	</div>
</div>