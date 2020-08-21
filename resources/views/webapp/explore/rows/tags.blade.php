	<div class="mb-3">
		<h5>{{ucfirst($title)}}</h5>
		<div class="custom-scroll dragscroll dragscroll-horizontal">
			<div>
				<div class="d-flex">
					@foreach($category as $tag)
						@if($loop->iteration <= $loop->count / 2)
						<button data-name="{{$tag->name}}" data-id="{{$tag->id}}" class="tag btn btn-light badge-pill m-2 px-3 py-1 text-nowrap">
							{{$tag->name}}
						</button>
						@endif
					@endforeach
				</div>
				<div class="d-flex">
					@foreach($category as $tag)
						@if($loop->iteration > $loop->count / 2)
						<button data-name="{{$tag->name}}" data-id="{{$tag->id}}" class="tag btn btn-light badge-pill m-2 px-3 py-1 text-nowrap">
							{{$tag->name}}
						</button>
						@endif
					@endforeach
				</div>
			</div>
		</div>
	</div>