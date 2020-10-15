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

@include('home.sections.youtube')

@endsection

@push('scripts')
@endpush