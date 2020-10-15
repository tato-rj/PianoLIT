@extends('layouts.app')

@push('header')
@endpush

@section('content')
@include('search.components.forms.app')

<section class="container py-5">
	<div class="row"> 
		@include('components.pieces.display', ['pieces' => $results->take(8)])
	</div>
</section>

<section class="container mb-5">
	<div class="row">
		<div class="col-lg-6 col-md-6 col-12 m-auto">
			<div class="text-center">
				<h2>Need more results?</h2>
				<p>Sign up for free or try our premium plan for 7 days</p>
			</div>
		</div>
	</div>
	<div class="row">
		@component('search.components.plan')
		<button class="btn px-0 text-nowrap mb-3" disabled>YOU'RE NOT REGISTERED</button>
		<ul class="list-flat">
			<li class="mb-1"><strong style="font-size: 140%">8</strong> results limit</li>
			<li><strong style="font-size: 140%">2</strong> queries per day</li>
		</ul>
		@endcomponent

		@component('search.components.plan', ['color' => 'light'])
		<a href="{{route('webapp.discover')}}" data-ios="{{config('app.stores.ios')}}" class="btn btn-outline-primary text-nowrap btn-block mb-3">SIGN UP FOR FREE</a>
		<ul class="list-flat">
			<li class="mb-2"><strong>Unlimited</strong> results</li>
			<li class="mb-2"><strong>Unlimited</strong> queries per day</li>
			<li class="mb-2">@fa(['icon' => 'apple', 'fa_type' => 'b'])iOS App</li>
			<li class="mb-2">@fa(['icon' => 'laptop'])Desktop App</li>
			<li>@fa(['icon' => 'video'])Advanced filters</li>
		</ul>
		@endcomponent

		@component('search.components.plan', ['color' => 'light'])
		<a href="{{route('webapp.discover')}}" data-ios="{{config('app.stores.ios')}}" class="btn btn-primary text-nowrap btn-block mb-3">@fa(['icon' => 'crown'])GO PREMIUM</a>
		<ul class="list-flat">
			<li class="mb-2"><strong>Unlimited</strong> results</li>
			<li class="mb-2"><strong>Unlimited</strong> queries per day</li>
			<li class="mb-2">@fa(['icon' => 'apple', 'fa_type' => 'b'])iOS App</li>
			<li class="mb-2">@fa(['icon' => 'laptop'])Desktop App</li>
			<li class="mb-2">@fa(['icon' => 'video'])Advanced filters</li>
			<li class="mb-2">@fa(['icon' => 'bolt'])Full access to all resources</li>
			<li>@fa(['icon' => 'video'])Request video tutorials</li>
		</ul>
		@endcomponent
	</div>
</section>
@endsection

@push('scripts')
@endpush