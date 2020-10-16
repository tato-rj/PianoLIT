<div class="col-12 p-2 mb-3">
	<div class="card rounded border-0">
		<a class="link-none" href="{{route('quizzes.show', $quiz->slug)}}">
		  <div class="row no-gutters">
		    <div class="col-lg-3">
				<div class="card-img-top bg-align-center h-100" style="background-image: url({{$quiz->cover_image()}});"></div>
		    </div>
		    <div class="col-lg-9">
		      <div class="card-body">
				<p class="text-muted mb-0"><small>{{$quiz->created_at->toFormattedDateString()}} &bull; {{count($quiz->questions)}} questions</small></p>
				<h5 class="card-title mb-1">{{$quiz->title}}</h5>
				<p class="card-text mb-1">{{$quiz->description}}</p>
				<p class="m-0 text-muted"><small>This quiz is <span class="text-{{$quiz->level->color}}">{{$quiz->level->name}}</span></small></p>
		      </div>
		    </div>
		  </div>
		</a>
	</div>
</div>