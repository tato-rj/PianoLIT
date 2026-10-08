@if($row['title'] === 'Recently viewed')
	@include('webapp.discover.rows.recently-viewed')
@elseif($row['title'] === 'Latest pieces')
	@include('webapp.discover.rows.latest')
@elseif($row['title'] === 'For you')
	@include('webapp.discover.rows.for-you')
@elseif($row['title'] === 'From women composers')
	@include('webapp.discover.rows.women-composers')
@else
<div class="mb-4">
	<h5 class="mb-3">{{$row['title']}}</h5>
	<div class="custom-scroll dragscroll dragscroll-horizontal card-scroll-row">
		<div class="d-flex pb-2" style="height: 144px;">
			@foreach($row['content'] as $card)
				@include('webapp.discover.cards.' . $row['type'], compact('hasFullAccess'))
			@endforeach
		</div>
	</div>
</div>
@endif
