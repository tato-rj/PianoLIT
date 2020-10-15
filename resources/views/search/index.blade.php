@extends('layouts.app')

@push('header')
@endpush

@section('content')
@include('search.components.forms.app')

<section class="container py-5">
	<div class="row"> 
		@include('components.pieces.display', ['pieces' => $results->take(8)])
	</div>
	@if($results->count() > 8)
	<div class="text-center text-muted mt-4">
		<i>You've reached your <strong>8</strong> results limit :/</i>
	</div>
	@endif
</section>

@include('search.components.plans')

@include('home.sections.youtube')
@endsection

@push('scripts')
@endpush