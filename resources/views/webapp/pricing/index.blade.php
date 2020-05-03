@extends('webapp.layouts.app')

@push('header')
<style type="text/css">
.best-value {
	font-size: 70%; 
	top: -14px; 
	left: -1px; 
	border-top-right-radius: 16px; 
	border-bottom-right-radius: 16px;
}
</style>
@endpush

@section('content')
@include('webapp.layouts.header', ['title' => 'Go Premium', 'subtitle' => 'Get the best of PianoLIT and start your FREE trial now!'])

@include('webapp.pricing.plans')

@include('webapp.pricing.features')

@include('webapp.pricing.faq')

@endsection

@push('scripts')
<script type="text/javascript">
$('#faq-accordion').on('show.bs.collapse', function (event) {
  $(event.target).siblings('div').find('i').removeClass('fa-plus').addClass(' fa-minus');
});

$('#faq-accordion').on('hide.bs.collapse', function (event) {
  $(event.target).siblings('div').find('i').removeClass('fa-minus').addClass(' fa-plus');
});
</script>
@endpush