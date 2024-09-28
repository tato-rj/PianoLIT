<div class="tab-pane fade show active" id="tab-about">
	@if($piece->media['performance'])
	<div class="mb-4 rounded-video video-container">
		@video([
			'classes' => 'w-100', 
			'id' => 'piece-performance', 
			'thumbnail' => asset('images/webapp/piano-thumbnail.jpg'),
			'url' => $piece->media['performance']->temp_video_url])
	</div>
	@endif

	@if($piece->hasDescription())
	<div class="mb-4">
		<h5 class="mb-3">What's this piece like?</h5>
		<div style="white-space: pre-wrap;">{{$piece->description}}</div>
	</div>
	@else
	<div class="mb-4">
		<h5 class="mb-3">About the composer</h5>
		<div id="composer-bio" class="mb-2">{{$piece->composer->biography}}</div>
		<div>To learn more about {{$piece->composer->last_name}} <a href="{{route('webapp.pieces.composer', $piece)}}">click here</a>.</div>
	</div>
	@endif

	<div class="mb-4 pb-4 border-bottom">
		<h5 class="mb-3">Who's this piece for?</h5>
		<div>{{$piece->for_who}}</div>
	</div>

	<div class="mb-4 pb-4 border-bottom">
		<div class="mb-2">
			<div class="d-flex flex-center flex-wrap">
				<div class="badge badge-pill alert-grey text-nowrap mx-2 mb-1">
					@fa(['icon' => 'file-alt']){{$piece->number_of_pages}}
				</div>
				<div class="badge badge-pill alert-grey text-nowrap mx-2 mb-1">
					@fa(['icon' => 'palette']){{$piece->period_name}}
				</div>
				<div class="badge badge-pill alert-grey text-nowrap mx-2 mb-1">
					@fa(['icon' => 'music']){{$piece->key}}
				</div>
			</div>
		</div>
		<p class="m-0"><strong class="text-brand">MOOD:</strong> {{ ucfirst(arrayToSentence($piece->mood()->pluck('name')->toArray())) }}.</p>
		@if($piece->technique()->isNotEmpty())
		<p class="m-0 mt-2"><strong class="text-brand">TECHNIQUE:</strong> {{ ucfirst(arrayToSentence($piece->technique()->pluck('name')->toArray())) }}.</p>
		@endif
	</div>

	<div class="mb-4">
		<h5 class="mb-3">Ranking</h5>
		@foreach($piece->rankings as $ranking => $label)
		@if($label)
		<div class="d-flex align-items-center mb-2">
			<img class="mr-2" style="width: 40px" src="{{asset('images/webapp/icons/'.$ranking.'.png')}}">
			<div class="text-nowrap">{{$label}}</div>
		</div>
		@endif
		@endforeach
	</div>

	@php($similar = $piece->similar())

	@if(! $similar->isEmpty())
	<div class="mb-4">
		<div class="d-flex d-apart mb-3">
			<h5 class="m-0">More like this</h5>
			<a href="{{route('webapp.pieces.similar', $piece)}}" class="btn-raw link-primary">View all</a>
		</div>
		<div class="custom-scroll dragscroll dragscroll-horizontal">
			<div class="d-flex pb-2" style="height: 144px;">
				@foreach($piece->similar()->take(16) as $card)
					@php($card->color = 'yellow')
					@php($card->subtitle = $card->composer->short_name)
					@include('webapp.discover.cards.piece', ['hasFullAccess' => $hasFullAccess])
				@endforeach
			</div>
		</div>
	</div>
	@endif

{{-- 	<div class="text-center mb-4">
		<a href="{{route('webapp.pieces.similar', $piece)}}" class="btn btn-wide btn-default">
			@fa(['icon' => 'folder-plus'])More like this</a>
	</div> --}}
</div>