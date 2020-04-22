@extends('webapp.layouts.app')

@push('header')
@endpush

@section('content')
@component('webapp.layouts.header', ['title' => 'Playlists', 'subtitle' => 'Explore the repertoire and discover new pieces every day'])
	<button class="btn btn-wide rounded-pill btn-outline-secondary">FOLLOW A PATH</button>
@endcomponent

@endsection

@push('scripts')
@endpush