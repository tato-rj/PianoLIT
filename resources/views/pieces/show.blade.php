@extends('layouts.app', ['title' => 'PianoLIT | ' . $piece->long_name])

@push('header')
<style type="text/css">

</style>
@endpush

@section('content')

<div class="container mt-5 mb-6">
	<div class="col-lg-8 col-md-10 col-12 mx-auto mb-6">

		<div class="mb-4">
			<span class="badge badge-pill mb-3 bg-{{$piece->level->name}}">{{$piece->level->name}}</span>
			<h5><strong>{{$piece->long_name}}</strong></h5>
			<p><i>by {{$piece->composer->name}}</i></p>
		</div>

		<div class="row mb-2">
			<div class="col-lg-6 col-md-6 col-12 mb-3">
				<a href="{{$piece->isPublicDomain ? storage($piece->score_path) : $piece->score_url}}" target="_blank" class="btn btn-block btn-light py-6">
					<h1><i class="fas text-grey fa-{{$piece->isPublicDomain ? 'cloud-download-alt' : 'shopping-bag'}}"></i></h1>
					<h5>{{$piece->isPublicDomain ? 'Download' : 'Buy'}} score</h5>
				</a>
			</div>
			<div class="col-lg-6 col-md-6 col-12 mb-3">
				<a href="{{route('search.similar', $piece->id)}}" target="_blank" class="btn btn-block btn-light py-6">
					<h1><i class="text-grey fas fa-gift"></i></h1>
					<h5>More like this</h5>
				</a>
			</div>
		</div>
		@if($piece->hasAudio())
		<div class="row mb-5">
			<div class="col-12">
				<audio controls class="w-100">
					<source src="{{storage($piece->audio_path)}}" type="audio/mp3">
				</audio>
			</div>
		</div>
		@endif

		<div class="row">
			@php($tags = $piece->tags()->mood()->get())
			@if(! $tags->isEmpty())
			<div class="col-12 mb-4">
				<h6 class="mb-3">What's this piece like?</h6>
				@foreach($tags as $tag)
				    @include('pieces.tag', ['color' => 'teal'])
				@endforeach
			</div>
			@endif

			@php($tags = $piece->tags()->technique()->get())
			@if(! $tags->isEmpty())
			<div class="col-12 mb-4">
				<h6 class="mb-3">What's this piece good for?</h6>
				@foreach($tags as $tag)
				    @include('pieces.tag', ['color' => 'blue'])
				@endforeach
			</div>
			@endif

			@php($tags = $piece->tags()->ranking()->get())
			@if(! $tags->isEmpty())
			<div class="col-12">
				<h6 class="mb-3">The level of this piece is equivalent to...</h6>
				@foreach($tags as $tag)
				    @include('pieces.tag', ['color' => 'orange'])
				@endforeach
			</div>
			@endif
		</div>
	</div>

	<div class="col-lg-8 col-md-9 col-sm-10 col-12 mx-auto border-top pt-5" id="screens-composition">
		<div class="text-center">
			<p class="mb-2 text-muted">Looking for more resources?</p>
			<h4>We're working on an iOS app for that!</h4>
		</div>
		@include('components.app.screens')

		<div class="text-center">
			<div style="max-width: 400px" class="mx-auto">
				<h4 class="mb-3">Coming out soon</h4>
				<p>Subscribe to our newletter and be the first one to know when the app is out!</p>
				@include('components.form.subscription')
			</div>
		</div>
	</div>
</div>

<div class="container mb-6">
  @include('components.sections.youtube')
</div>

{{-- 			@if($piece->isPublicDomain)
				<div class="embed-responsive embed-responsive-a4 mb-4">
					<embed type="application/pdf" src="{{storage($piece->score_path)}}" class="embed-responsive-item" frameborder="0">
				</div>
				<div class="text-center w-100">
					<a href="{{storage($piece->score_path)}}" class="btn btn-wide btn-teal"><i class="fas fa-cloud-download-alt mr-2"></i>Download score</a>
				</div>
			@else
				<div class="text-center mb-8">
					<p>This piece is <strong><u>not</u></strong> in the public domain.</p>
					<a href="{{$piece->score_url}}" target="_blank" class="btn btn-teal btn-wide"><i class="fas fa-shopping-bag mr-2"></i>Buy the score here</a>
				</div>
			@endif --}}
@include('components.overlays.subscribe.model-2')
@endsection

@push('scripts')
@include('components.addthis')
<script type="text/javascript">
let scrollMark = $('#screens-composition').offset().top - 300;

$(window).scroll(function() {
  let scrollTop = $(this).scrollTop();
console.log(scrollMark);
  if (scrollTop > scrollMark){
    $('#screens-container img:nth-child(2)').css('margin-left', '192px');
    $('#screens-container img:nth-child(3)').css('margin-left', '-192px');
    $('#screens-container img:nth-child(4)').css('margin-left', '328px');
    $('#screens-container img:nth-child(5)').css('margin-left', '-328px');
  } else {
    $('#screens-container img').css('margin-left', '0');
  }
});
</script>
<script type="text/javascript">
$("#gift-overlay").showAfter(5);
</script>
@endpush