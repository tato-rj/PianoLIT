@extends('layouts.pdf', ['title' => 'My studio policy'])

@push('header')
<style type="text/css">

:root {
  --primary-color: {{$policy->colors()[0]}};
  --secondary-color: {{$policy->colors()[1]}};
  --tertiary-color: {{$policy->colors()[2]}};
}

</style>

<link rel="stylesheet" type="text/css" href="{{asset('css/studio-policies/layout.css')}}">
<link rel="stylesheet" type="text/css" href="{{$policy->style()}}">
@endpush

@section('content')
@foreach(\App\StudioPolicy::sections() as $section)
	@include("pdf.studio-policy.sections.$section")
@endforeach

@endsection