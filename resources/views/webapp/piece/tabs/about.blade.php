<div class="tab-pane fade show active" id="tab-about">
	<div class="mb-3 pb-4 border-bottom">
		<div class="d-flex flex-center flex-wrap">
			<div class="text-nowrap mx-2 mb-1">
				@fa(['icon' => 'file-alt', 'mr' => 1, 'color' => 'brand']){{$piece->number_of_pages}}
			</div>
			<div class="text-nowrap mx-2 mb-1">
				@fa(['icon' => 'palette', 'mr' => 1, 'color' => 'brand']){{$piece->period_name}}
			</div>
			<div class="text-nowrap mx-2 mb-1">
				@fa(['icon' => 'music', 'mr' => 1, 'color' => 'brand']){{$piece->key}}
			</div>
		</div>
	</div>
	
	@if($piece->hasDescription())
	<div class="mb-4 pb-4 border-bottom">
		<h5 class="mb-3">What's this piece like?</h5>
		<div style="white-space: pre-wrap;">{{$piece->description}}</div>
	</div>
	@else
	<div class="mb-4 pb-4 border-bottom">
		<h5 class="mb-3">About the composer</h5>
		<div id="composer-bio" class="mb-2">{{$piece->composer->biography}}</div>
		<div>To learn more about {{$piece->composer->last_name}} <a href="{{route('webapp.pieces.composer', $piece)}}" class="link-blue">click here</a>.</div>
	</div>
	@endif

	<div class="mb-4 pb-4 border-bottom">
		<h5 class="mb-3">Who's this piece for?</h5>
		<div>{{$piece->for_who}}</div>
	</div>

	<div class="mb-4 pb-4 border-bottom">
		<p class="m-0"><strong class="text-brand">MOOD:</strong> {{ ucfirst(arrayToSentence($piece->mood()->pluck('name')->toArray())) }}.</p>
		@if($piece->technique()->isNotEmpty())
		<p class="m-0 mt-2"><strong class="text-brand">TECHNIQUE:</strong> {{ ucfirst(arrayToSentence($piece->technique()->pluck('name')->toArray())) }}.</p>
		@endif
	</div>

	<div class="mb-4 pb-4">
		<h5 class="mb-3">Ranking</h5>
		@foreach($piece->rankings as $ranking => $label)
		<div class="d-flex align-items-center mb-2">
			<img class="mr-2" style="width: 40px" src="{{asset('images/webapp/icons/'.$ranking.'.png')}}">
			<div class="text-nowrap">{{$label}}</div>
		</div>
		@endforeach
	</div>

	<div class="text-center mb-4">
		<a href="{{route('webapp.pieces.similar', $piece)}}" class="btn rounded-pill btn-default">
			@fa(['icon' => 'folder-plus'])More like this</a>
	</div>
</div>