@extends('webapp.layouts.app')

@push('header')
@endpush

@section('content')
@component('webapp.layouts.header', ['title' => 'Discover', 'subtitle' => 'Take a quick tour to find the perfect piece for you'])
	<button class="btn btn-wide rounded-pill btn-outline-secondary">FIND YOUR MATCH</button>
@endcomponent

@foreach($rows as $row)
	@include('webapp.discover.rows.' . $row['row'])
@endforeach
@endsection

@push('scripts')
@endpush