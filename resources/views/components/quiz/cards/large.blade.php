<div class="col-lg-4 col-md-6 col-12 p-3">
	<div class="card border-0 shadow-light w-100 t-2">
		<a class="link-none" href="{{route('quizzes.show', $quiz->slug)}}">
			<div class="card-img-top rounded-top bg-align-center position-relative" style="background-image: url({{$quiz->cover_image()}}); height: 160px">
				@if($quiz->is_new)
				<div class="absolute-top-right"><span class="badge badge-light text-green"><small><strong>NEW</strong></small></span></div>
				@endif
				<div class="card-overlay h-100 t-2" style="opacity: 0">
					<div class="text-white overlay-blue d-flex flex-center rounded-top"><i class="fas fa-eye fa-3x"></i></div>
				</div>
			</div>
			<div class="card-body rounded-bottom">
				<p class="text-muted mb-1"><small>{{$quiz->created_at->toFormattedDateString()}} &bull; {{count($quiz->questions)}} questions</small></p>
				<h5 class="card-title mb-2">{{$quiz->title}}</h5>
				<p class="card-text mb-2">{{$quiz->description}}</p>
				<p class="m-0 text-muted"><small>This quiz is <span class="text-{{$quiz->level->color}}">{{$quiz->level->name}}</span></small></p>
			</div>
		</a>
	</div>
</div>