<div class="tab-pane fade show active mb-5" id="tab-about">
	<div class="row">
		@if($piece->media['performance'])
		<div class="col-lg-6 col-12 mb-4 rounded-video video-container {{$piece->media['performance']->moments->isEmpty() ? 'piece-about-video' : ''}}">
			@video([
				'classes' => 'w-100',
				'id' => 'piece-performance',
                'moments' => $piece->media['performance']->listeningMoments(),
	            'previewSeconds' => $hasMediaAccess ? null : $previewSeconds,
				'thumbnail' => asset('images/webapp/piano-thumbnail.jpg'),
				'url' => $piece->media['performance']->video_url])
		</div>
		@endif

		<div class="{{$piece->media['performance'] ? 'col-lg-6 col-12' : 'col-12'}} mb-4">
{{-- 			<div class="d-flex {{$piece->media['performance'] ? null : 'flex-center'}} flex-wrap mb-3">
				<div class="badge rounded-pill alert-grey text-nowrap mx-2 mb-1">
					@icon('file-text'){{$piece->number_of_pages}}
				</div>
				<div class="badge rounded-pill alert-grey text-nowrap mx-2 mb-1">
					@icon('palette'){{$piece->period_name}}
				</div>
				<div class="badge rounded-pill alert-grey text-nowrap mx-2 mb-1">
					@icon('music'){{$piece->key}}
				</div>
			</div> --}}
			@if($piece->hasDescription())
			<div class="mb-3 piece-description" data-piece-description>
				<h5 class="mb-2">What's this piece like?</h5>
				<div class="piece-description__content" id="piece-description">{{$piece->description}}</div>
				<div class="d-flex justify-content-end">
					<button type="button" class="piece-description__toggle" aria-controls="piece-description" aria-expanded="false" hidden>Read more</button>
				</div>
			</div>
			@else
			<div class="mb-3">
				<h5 class="mb-2">About the composer</h5>
				<div id="composer-bio" class="mb-2">{{$piece->composer->biography}}</div>
				<div>To learn more about {{$piece->composer->last_name}} <a href="{{route('webapp.pieces.composer', $piece)}}">click here</a>.</div>
			</div>
			@endif
				<h5 class="mb-2">Who's this piece for?</h5>
				<div>{{$piece->for_who}}</div>
		</div>
	</div>
	<div class="">
{{-- 		<div class="mb-4 pb-4 border-bottom">
			<div class="mb-2">
				<div class="d-flex flex-center flex-wrap">
					<div class="badge rounded-pill alert-grey text-nowrap mx-2 mb-1">
						@icon('file-text'){{$piece->number_of_pages}}
					</div>
					<div class="badge rounded-pill alert-grey text-nowrap mx-2 mb-1">
						@icon('palette'){{$piece->period_name}}
					</div>
					<div class="badge rounded-pill alert-grey text-nowrap mx-2 mb-1">
						@icon('music'){{$piece->key}}
					</div>
				</div>
			</div>
			<p class="m-0"><strong class="text-brand">MOOD:</strong> {{ ucfirst(arrayToSentence($piece->mood()->pluck('name')->toArray())) }}.</p>
			@if($piece->technique()->isNotEmpty())
			<p class="m-0 mt-2"><strong class="text-brand">TECHNIQUE:</strong> {{ ucfirst(arrayToSentence($piece->technique()->pluck('name')->toArray())) }}.</p>
			@endif
		</div> --}}

		<div class="mb-4">
			<h5 class="mb-3">Ranking</h5>
			@foreach($piece->rankings as $ranking => $label)
			@if($label)
			<div class="d-flex align-items-center mb-2">
				<img class="me-2" style="width: 40px" src="{{asset('images/webapp/icons/'.$ranking.'.png')}}">
				<div class="text-nowrap">{{$label}}</div>
			</div>
			@endif
			@endforeach
		</div>


		@foreach($recommendationRows as $recommendationRow)
		<div class="mb-4">
			<div class="d-flex d-apart mb-3">
				<h5 class="m-0">{{$recommendationRow['title']}}</h5>
				<a href="{{$recommendationRow['url']}}" class="btn-raw link-primary" aria-label="View all: {{$recommendationRow['title']}}">View all</a>
			</div>
			<div class="custom-scroll dragscroll dragscroll-horizontal">
				<div class="d-flex pb-2" style="height: 144px;">
					@foreach($recommendationRow['pieces'] as $card)
						@php($card->color = $recommendationRow['color'])
						@php($card->subtitle = $card->composer->short_name)
						@include('webapp.discover.cards.piece', ['hasFullAccess' => $hasFullAccess])
					@endforeach
				</div>
			</div>
		</div>
		@endforeach
	</div>
</div>
