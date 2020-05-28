<div class="mb-4">
	<h5 class="mb-3">{{$row['title']}}</h5>
	<div class="custom-scroll dragscroll dragscroll-horizontal">
		<div class="d-flex pb-2" style="height: 144px;">
			@foreach($row['content'] as $card)
				<div class="mr-3 cursor-pointer rounded h-100 d-flex align-items-end p-3 {{$row['type'] == 'piece' ? 'piece' : 'search'}}-card position-relative" 
				data-url="{{$row['type'] == 'piece' ? route('webapp.pieces.show', $card) : route('webapp.search.results', ['search' => $card->name])}}"
				style="@include('webapp.discover.rows.gradient-css')">
					@fa(['icon' => 'lock', 'classes' => 'absolute-top-left opacity-4', 'color' => 'white', 'if' => $row['type'] == 'piece' && ! auth()->user()->isAuthorized()])
					@pill(['label' => 'NEW', 'color' => 'white', 'text' => 'danger', 'pos' => 'top-right', 'if' => $row['type'] == 'piece' && $card->is_new])
					<div class="text-white" style="width: 164px">
						<p class="mb-1 clamp-2" style="line-height: 1; font-size: 92%"><strong>{{$card->name}}</strong></p>
						<p class="m-0" style="line-height: 1"><small>{{$card->subtitle}}</small></p>
					</div>
				</div>
			@endforeach
		</div>
	</div>
</div>