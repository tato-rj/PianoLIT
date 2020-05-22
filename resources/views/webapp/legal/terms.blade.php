@extends('webapp.layouts.app')

@section('content')
@include('webapp.layouts.header')

<section>
	@include('components.legal.terms')
</section>
@endsection