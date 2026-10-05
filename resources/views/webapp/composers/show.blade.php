@extends('webapp.layouts.app', ['title' => $composer->name, 'wideContent' => true])


@push('header')
<link rel="preload" href="{{ asset('css/vendor/flag-icon/flag-icon.min.css') }}" as="style">
<link href="{{ asset('css/vendor/flag-icon/flag-icon.min.css') }}" rel="stylesheet">
@endpush

@section('content')
@include('webapp.layouts.header')

<section id="composer-profile" aria-labelledby="composer-name">
	@include('webapp.composers.page-profile')
</section>
@endsection
