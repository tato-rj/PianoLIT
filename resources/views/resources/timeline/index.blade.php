@extends('layouts.app', [
	'title' => 'Music Timeline | ' . config('app.name'),
	'shareable' => [
		'keywords' => 'music history,music theory,timeline,composers,famous pieces,symphony',
		'title' => 'Music Timeline',
		'description' => 'View the major music events in connection with world history',
		'thumbnail' => asset('images/misc/thumbnails/staff.jpg'),
		'created_at' => carbon('28-08-2019'),
		'updated_at' => carbon('28-08-2019')
	]])

@push('header')
<style type="text/css">
.fadeInUp {
	animation-duration: .2s;
}

.border-pill span {
  opacity: 0;
}

.border-pill:hover span {
  opacity: 1;
}
</style>
@endpush

@section('content')
@include('components.title', [
	'version' => '1.0',
	'title' => 'Music Timeline', 
	'subtitle' => 'View the major music events in connection with world history'])

@if(app()->isLocal() || request()->has('dev'))
<div class="container mb-4">
	<div class="row mb-6">
		<div class="col-lg-8 col-md-8 col-12 mx-auto">
		  <div class="accordion" id="timeline">
        @foreach($timeline as $century => $decades)
        @include('resources/timeline/century')
        @endforeach  
      </div>
		</div>
	</div>
</div>
@else
<div class="my-6">
  @include('components/animations/workers')
  <h3 class="text-grey text-center my-4">Coming up soon!</h3>
</div>
@endif

<div class="container mb-6">
	@include('components.sections.feedback')
	@include('components.sections.youtube')
</div>

@endsection

@push('scripts')
@include('components.addthis')
<script type="text/javascript">
$('.collapse').on('hide.bs.collapse', function () {
  let $title = $(this).prev('div');
  $title.find('i').removeClass('fa-caret-up').addClass('fa-caret-down');
  $title.find('span small').text('click to show');
});

$('.collapse').on('show.bs.collapse', function () {
  let $title = $(this).prev('div');
  $title.find('i').addClass('fa-caret-up').removeClass('fa-caret-down');
  $title.find('span small').text('click to hide');
});
</script>
@endpush