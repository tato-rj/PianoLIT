@extends('webapp.layouts.app')

@push('header')
@endpush

@section('content')
@component('webapp.layouts.header', ['title' => 'Playlists', 'subtitle' => 'Explore the repertoire and discover new pieces every day'])
	<button class="btn btn-wide rounded-pill btn-outline-secondary" data-toggle="modal" data-target="#journey-modal">FOLLOW A PATH</button>
@endcomponent
<div class="row">
@foreach($playlists as $playlist)
	@include('webapp.playlists.card')
@endforeach
</div>

@include('webapp.playlists.journey')
@endsection

@push('scripts')
@endpush