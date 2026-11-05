@php($hasMoments = $piece->media['performance'] && ! $piece->media['performance']->moments->isEmpty())
<div class="tab-pane fade show active" id="tab-about" role="tabpanel" aria-labelledby="main-nav-tab-about" tabindex="0">
	<div class="row">
		@if($piece->media['performance'])
		<div class="{{$hasMoments ? 'col-lg-12' : 'col-lg-6 mx-auto piece-about-video'}} col-12 rounded-video video-container piece-page-video" style="margin-bottom: 2rem;">
			@video([
				'classes' => 'w-100',
				'id' => 'piece-performance',
                'splitMoments' => true,
                'moments' => $piece->media['performance']->listeningMoments(),
	            'accessPrompt' => app(\App\Services\WebApp\AccessPolicy::class)->promptTarget(auth('web')->user(), 'performance', $piece),
            'previewSeconds' => $hasPerformanceAccess ? null : $videoPreviewSeconds,
				'thumbnail' => asset('images/webapp/piano-thumbnail.jpg'),
				'url' => $piece->media['performance']->video_url])
		</div>
		@endif

		<div class="col-12" style="margin-bottom: 2rem;">
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
			<div class="piece-description"  style="margin-bottom: 2rem;" data-piece-description>
				<h5 class="mb-2">A closer look</h5>
				<div class="piece-description__content" id="piece-description">{{$piece->description}}</div>
				<div class="d-flex justify-content-end">
					<button type="button" class="piece-description__toggle" aria-controls="piece-description" aria-expanded="false" hidden>Read more</button>
				</div>
			</div>
			@else
			<div class="mb-3">
				<h5 class="mb-2">About the composer</h5>
				<div id="composer-bio" class="mb-2">{{$piece->composer->biography}}</div>
				<div>To learn more about {{$piece->composer->last_name}} <a href="{{route('webapp.composers.show', $piece->composer)}}">click here</a>.</div>
			</div>
			@endif
				<h5 class="mb-2">Is this your next piece?</h5>
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

		<div class="" style="margin-bottom: 2rem;">
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


		@include('webapp.piece.recommendations')
	</div>
</div>
