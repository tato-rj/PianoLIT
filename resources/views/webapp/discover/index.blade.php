@extends('webapp.layouts.app')

@push('header')
@endpush

@section('content')
<div class="text-center mb-4">
	<h1>Discover</h1>
	<p style="max-width: 80%" class="mx-auto">Take a quick tour to find the perfect piece for you</p>
	<button class="btn btn-wide rounded-pill btn-outline-secondary">FIND YOUR MATCH</button>
</div>

@foreach($rows as $row)
	@include('webapp.discover.rows.' . $row['row'])
@endforeach
@endsection

@push('scripts')
@endpush