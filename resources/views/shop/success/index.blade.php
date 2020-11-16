@extends('layouts.app', ['title' => 'Thank you for your purchase!'])

@push('header')
<style type="text/css">
</style>
@endpush

@section('content')

<section class="container mb-5">
	<div class="row mb-5">
	  @include('shop.success.summary')
	  @include('shop.success.product')
	</div>
	<div class="text-center">
		<h6>How are we doing? <a href="mailto:{{config('app.emails.general')}}?subject=My feedback for the PianoLIT team" target="_blank">Give your feedback</a> on what we can improve</h6>
	</div>
</section>

@endsection

@push('scripts')
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/downloadjs/1.4.8/download.min.js"></script>
<script type="text/javascript">
let $countdown = $('#countdown-seconds');

if ($countdown.length) {
	let seconds = 3;
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