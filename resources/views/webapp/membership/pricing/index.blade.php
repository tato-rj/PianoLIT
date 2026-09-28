@extends('webapp.layouts.app')

@push('header')
<style type="text/css">
.best-value {
	font-size: 70%; 
	top: -14px; 
	left: -1px; 
	border-top-right-radius: var(--radius);
	border-bottom-right-radius: var(--radius);
}
</style>
@endpush

@section('content')
@include('webapp.layouts.header', ['title' => 'Go Premium', 'subtitle' => 'Get the best of PianoLIT and start your FREE trial now!'])

@include('webapp.membership.pricing.plans')

@include('webapp.membership.pricing.features')

@include('webapp.membership.pricing.faq')

@endsection

@push('scripts')
<script type="text/javascript">
$('#faq-accordion').on('show.bs.collapse', function (event) {
  $(event.target).siblings('div').find('i').removeClass('icon-plus').addClass(' icon-minus');
});

$('#faq-accordion').on('hide.bs.collapse', function (event) {
  $(event.target).siblings('div').find('i').removeClass('icon-minus').addClass(' icon-plus');
});
</script>
@endpush