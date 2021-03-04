<div class="carousel-item {{$loop->first ? 'active' : null}}">
	<div class="my-2">
		<div class="text-center mb-3">
			<div class="badge badge-pill alert-grey mb-2">QUESTION {{$loop->iteration}} OF {{$loop->count}}</div>
			<h5>{{$question}}</h5>
		</div>

		{{$slot}}
	</div>
</div>