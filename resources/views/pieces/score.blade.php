@extends('layouts.app', ['title' => 'PianoLIT Music Score'])

@push('header')
<style type="text/css">
.embed-responsive-a4 {
  padding-bottom: 141.42%;
}
</style>
@endpush

@section('content')

@include('components.title', [
    'title' => 'Music Score', 
    'subtitle' => 'Download or view piano scores'])


<div class="container mt-5 mb-6">
	<div class="col-lg-8 col-md-9 col-sm-10 col-12 mx-auto">
		@if($piece)
		<div class="mb-4">
			<h5><strong>{{$piece->long_name}}</strong></h5>
			<p><i>by {{$piece->composer->name}}</i></p>
		</div>
		@if($piece->isPublicDomain)
			<div class="embed-responsive embed-responsive-a4">
				<embed type="application/pdf" src="{{storage($piece->score_path)}}" class="embed-responsive-item" frameborder="0">
			</div>
		@else
			<div class="text-center mb-8">
				<p>This piece is <strong><u>not</u></strong> in the public domain.</p>
				<a href="{{$piece->score_url}}" target="_blank" class="btn btn-teal btn-wide"><i class="fas fa-shopping-bag mr-2"></i>Buy the score here</a>
			</div>
		@endif
		@else
		<div class="text-center my-8">
			<h6 class="text-muted mb-3"><i>Sorry, we couldn't find the piece you were looking for...</i></h6>
			<img src="{{asset('images/icons/crying.svg')}}" style="width: 60px">
		</div>
		@endif
	</div>
</div>

<div class="container mb-6">
  @include('components.sections.youtube')
</div>

@include('components.overlays.subscribe.paper-plane')
@endsection

@push('scripts')
@include('components.addthis')

@endpush