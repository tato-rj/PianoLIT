@extends('layouts.raw')

@section('content')
@php($publicSite = request()->getHost() !== 'my.'.config('app.short_url'))
<div @if($publicSite) class="public-site d-flex flex-column min-vh-100" @endif>
@if($publicSite)
    @include('components.public-header')
@endif
<main class="w-100 d-flex flex-center {{ $publicSite ? 'py-5 flex-grow-1' : 'h-100vh' }}">
	<div class="text-center">
		<img src="{{asset(mix('images/brand/app-icon.svg'))}}" style="border-radius: 20%; width: 80px" class="mb-4">
		<h3>We're sorry to see you go!</h3>
		<h4>Your email has been successfully removed from this list.</h4>
	</div>
</main>
@if($publicSite)
    @include('components.public-footer')
@endif
</div>
@endsection
