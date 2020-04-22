<div class="mb-4">
	<h5>{{$row['title']}}</h5>
	<div class="custom-scroll dragscroll dragscroll-horizontal">
		<div class="d-flex pb-2" style="height: 124px;">
			@foreach($row['content'] as $card)
				<div class="mr-3 rounded h-100 d-flex align-items-end p-3 gallery-card" style="background: linear-gradient(to right, {{gradient($card->color)[0]}}, {{gradient($card->color)[1]}});">
					<div class="text-white" style="width: 164px">
						<p class="m-0 clamp-2" style="line-height: 1; font-size: 92%"><strong>{{$card->name}}</strong></p>
						<p class="m-0"><small>{{$card->subtitle}}</small></p>
					</div>
				</div>
			@endforeach
		</div>
	</div>
</div>