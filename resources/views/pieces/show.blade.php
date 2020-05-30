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
			@if($piece->isPublicDomain)
			<div class="col-lg-6 col-md-6 col-12">
				<div class="embed-responsive embed-responsive-a4 mb-4">
					<embed type="application/pdf" src="{{storage($piece->score_path)}}" class="embed-responsive-item" frameborder="0">
				</div>
			</div>
			@endif

			<div class="col-lg-6 col-md-6 col-12">
				@php($tags = $piece->tags()->mood()->get())
				@if(! $tags->isEmpty())
				<div class=" mb-4">
					<h6 class="mb-3">What's this piece like?</h6>
					@foreach($tags as $tag)
					    @include('pieces.tag', ['color' => 'teal'])
					@endforeach
				</div>
				@endif

				@php($tags = $piece->tags()->technique()->get())
				@if(! $tags->isEmpty())
				<div class=" mb-4">
					<h6 class="mb-3">What's this piece good for?</h6>
					@foreach($tags as $tag)
					    @include('pieces.tag', ['color' => 'blue'])
					@endforeach
				</div>
				@endif

				@php($tags = $piece->tags()->ranking()->get())
				@if(! $tags->isEmpty())
				<div class="">
					<h6 class="mb-3">The level of this piece is equivalent to...</h6>
					@foreach($tags as $tag)
					    @include('pieces.tag', ['color' => 'orange'])
					@endforeach
				</div>
				@endif
				<hr class="my-4">
				<div class="text-center w-100 mb-4">
					@if($piece->isPublicDomain)	
						<a href="{{storage($piece->score_path)}}" target="_blank" class="btn btn-block btn-wide btn-teal"><i class="fas fa-cloud-download-alt mr-2"></i>Download score</a>
					@else
						<a href="{{$piece->score_url}}" target="_blank" class="btn btn-block btn-teal btn-wide"><i class="fas fa-shopping-bag mr-2"></i>Buy the score here</a>
					@endif
				</div>
				@if($piece->hasAudio())
				<div>
				<audio controls class="w-100">
					<source src="{{storage($piece->audio_path)}}" type="audio/mp3">
				</audio>
				</div>
				@endif
			</div>
		</div>
	</div>

	<div class="col-lg-8 col-md-9 col-sm-10 col-12 mx-auto border-top pt-5" id="screens-composition">
		<div class="text-center">
			<h4 class="mb-4">Looking for more resources?</h4>
			@include('components.buttons.download')
		</div>
		@include('components.app.screens')
	</div>
</div>

<div class="container mb-6">
  @include('components.sections.youtube')
</div>

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