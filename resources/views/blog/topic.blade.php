@extends('layouts.app', ['title' => 'PianoLIT Blog'])

@section('content')
<section class="container mb-6">
	<div class="row">
    <div class="col-12 border-bottom mb-4 pb-4">
      <p class="text-muted mb-1"><small>POSTS ABOUT</small></p>
      <h2>{{ucfirst($topic->name)}}</h2>
    </div>
		<div class="col-lg-3 col-md-3 col-12">
      <p><strong>Other topics</strong></p>
      <div class="d-flex flex-wrap">
        @each('blog.components.topic', $topics, 'topic')
      </div>
    </div>
    <div class="col-lg-9 col-md-9 col-12">
      @each('blog.components.cards.horizontal', $posts, 'post')
    </div>

	</div>
</section>

<div class="container mb-6">
  @include('components.sections.youtube')
</div>
@endsection
