@extends('webapp.layouts.app')

@push('header')
@endpush

@section('content')
@component('webapp.layouts.header', ['title' => 'My Pieces', 'subtitle' => 'Quickly access your favorites or see your tutorial requests'])
	<button class="btn btn-wide rounded-pill btn-outline-secondary">FAVORITES</button>
@endcomponent

@endsection

@push('scripts')
@endpush