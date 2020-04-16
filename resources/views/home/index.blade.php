@extends('layouts.app')

@push('header')
@endpush

@section('content')

	@include('home.sections._lead')
  @include('home.sections.highlights')
  @include('home.sections.bar')
  @include('home.sections.tags')
	@include('home.sections.composition')
  @include('home.sections.youtube')
	@include('home.sections.testimonials')
	
@endsection

@push('scripts')
<script type="text/javascript" src="{{asset('js/components/testimonials.js')}}"></script>
<script type="text/javascript" src="{{asset('js/components/tagsearch.js')}}"></script>
<script type="text/javascript" src="{{asset('js/animations/phonescreens.js')}}"></script>

<script type="text/javascript">
$("#subscribe-overlay").showAfter(5);
</script>
@endpush