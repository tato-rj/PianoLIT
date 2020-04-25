@extends('webapp.layouts.app')

@push('header')
@endpush

@section('content')
@include('webapp.layouts.header')

<div class="text-center mb-3">
	@if($playlist->cover_image)
	<img src="{{$playlist->cover_image}}" style="width: 180px" class="rounded mb-4">
	@endif
	<h3>{{$playlist->name}}</h3>
	<p>{{$playlist->description}}</p>
</div>

@each('webapp.components.piece', $playlist->pieces, 'piece')
@endsection

@push('scripts')
@endpush