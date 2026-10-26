@php($infographicDownload = isset($purchase) && $purchase->item instanceof \App\Infograph\Infograph)
@extends('layouts.app', ['title' => $infographicDownload ? 'Your infographic download | PianoLIT Learn' : 'Thank you for your purchase!', 'bodyClass' => $infographicDownload ? 'learn-home' : null])

@push('header')
<style type="text/css">
</style>
@endpush

@section('content')

<section class="container mb-5 {{ $infographicDownload ? 'py-4' : '' }}">
	@if($infographicDownload)
		@include('components.navigation.breadcrumb', ['items' => [
			['label' => 'Home', 'url' => route('home')],
			['label' => 'Learn', 'url' => route('learn.home')],
			['label' => 'Infographics', 'url' => route('resources.infographs.index')],
			['label' => rtrim($purchase->item->name, '. '), 'url' => $purchase->item->showRoute()],
			['label' => 'Download'],
		]])
	@endif
	<div class="row mb-5">
	  @include('shop.success.summary')
	  @include('shop.success.product')
	  @include('shop.success.return')
	</div>
	<div class="text-center">
		<h5>How are we doing?</h5>
		<h6><a href="mailto:{{config('app.emails.general')}}?subject=My feedback for the PianoLIT team" target="_blank">Give your feedback</a> on what we can improve, we'll love to hear from you.</h6>
	</div>
</section>

@endsection

@push('scripts')
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/downloadjs/1.4.8/download.min.js"></script>
<script type="text/javascript">
let $countdown = $('#countdown-seconds');

if ($countdown.length) {
	let seconds = 5;
	let timer = setInterval(function() {
	  if (seconds < 1) {
	    clearInterval(timer);
	    console.log($countdown.data('href'));
		window.location.href = $countdown.data('href');
	  } else {
	  	seconds -= 1;
	  	$countdown.text(seconds);
	  }
	}, 1000);
}
</script>
@endpush
