@extends('layouts.pdf', ['title' => 'My studio policy'])

@push('header')
<style>
	body {
		padding: 3rem;
		font-family: 'Baskervville-Regular', serif;
	}

	h1, h2, h3, h4, h5 {
		/*font-family: 'Baskervville-Regular', serif;*/
		font-weight: 400;
	}

	p {
		/*font-family: 'OpenSans-Regular', sans-serif;*/
		margin-top: 0;
	}

	.page-break {
		page-break-after: always;
	}

	.bold {
		font-weight: bold;
	}

	.m-0 {
		margin: 0!important;
	}

	.mb-1 {
		margin-bottom: .25rem!important;
	}

	.mb-2 {
		margin-bottom: .5rem!important;
	}

	.mb-3 {
	    margin-bottom: 1rem!important;
	}

	.p-0 {
		padding: 0!important;
	}

	.pb-1 {
		padding-bottom: .25rem!important;
	}

	.pb-2 {
		padding-bottom: .5rem!important;
	}

	.pb-3 {
	    padding-bottom: 1rem!important;
	}

	.text-center {
		text-align: center;
	}

	.text-left {
		text-align: left;
	}

	.list-style-none {
		list-style: none;
	}

	.square {
		height: 10px;
		width: 10px;
		border: 1px solid black;
		display: inline-block;
		margin-right: 8px;
	}

	.signature {
		border-top: 1px solid black; 
		margin-top: 5rem; 
		padding-top: .5rem;
		width: 50%;
	}

	.box {
		margin-bottom: 3rem; 
		/*border: 1px solid; */
		background-color: #f8f9fa!important;
		border-radius: .25rem!important;
		padding: .8rem 2rem;
	}

	.border-bottom {
	    border-bottom: 1px solid #dee2e6!important;
	}
</style>
@endpush

@section('content')

@foreach(['header', 'general', 'performances', 'lessons', 'materials', 'scheduling', 'makeups', 'withdrawal', 'instrument', 'signatures'] as $section)
	@include("pdf.studio-policy.sections.$section")
@endforeach

@endsection