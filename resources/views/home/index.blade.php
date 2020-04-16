@extends('layouts.app')

@push('header')
<style type="text/css">

</style>
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
<script type="text/javascript" src="{{asset('js/animations/phonescreens.js')}}"></script>

<script type="text/javascript">
$("#subscribe-overlay").showAfter(5);
</script>

<script type="text/javascript">

$('#tags-search .tag').on('click', function() {
  let $btn = $(this);
  $btn.attr('disabled', true).toggleClass('btn-teal');
  
  let ids = $('#tags-search .btn-teal').attrToArray('data-id');
  let names = $('#tags-search .btn-teal').attrToArray('data-name');
  let $container = $('#pieces-container');
  let $label = $('#pieces-label');

  $container.parent().addClass('opacity-4');

  axios.get("{{route('load-pieces')}}", {params: {'ids': ids, 'names': names}})
    .then(function(response) {
      $container.html(response.data.view); 
      $container.parent().removeClass('opacity-4');
      $label.text(response.data.label);
    })
    .catch(function(error) {
      console.log(error);
    })
    .then(function() {
      $btn.attr('disabled', false);
    });
});

</script>
@endpush