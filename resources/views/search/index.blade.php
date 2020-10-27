@extends('layouts.app')

@push('header')
@endpush

@section('content')
@include('search.components.forms.app')

<section class="container py-5">
	<div class="row position-relative"> 
		@include('search.components.results.pieces', ['pieces' => $results->take(8)])

		<div style="
		position: absolute;
		bottom: 0;
		left: 0;
		width: 100%;
		height: 40%;
background: rgb(255,255,255);
background: linear-gradient(0deg, rgba(255,255,255,1) 25%, rgba(255,255,255,0) 100%);">
			
		</div>
	</div>
</section>

@include('search.components.plans')

@include('home.sections.youtube')
@endsection

@push('scripts')
@endpush