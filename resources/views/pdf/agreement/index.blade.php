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

	.mb-1 {
		margin-bottom: .25rem!important;
	}

	.mb-2 {
		margin-bottom: .5rem!important;
	}

	.m-0 {
		margin: 0!important;
	}

	.text-center {
		text-align: center;
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
</style>
@endpush

@section('content')

@include('pdf.agreement.sections.header')

@include('pdf.agreement.sections.overview')

@include('pdf.agreement.sections.recitals')

@include('pdf.agreement.sections.tuition')

@include('pdf.agreement.sections.scheduling')

@include('pdf.agreement.sections.make-ups')

@include('pdf.agreement.sections.withdrawal')

@include('pdf.agreement.sections.instrument')

@include('pdf.agreement.sections.signatures')

@endsection