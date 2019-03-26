@extends('layouts.app')

@push('header')
<style type="text/css">

</style>
@endpush

@section('content')

	@include('welcome.sections._lead')
	@include('welcome.sections.composition')
	@include('welcome.sections.tags')
	@include('welcome.sections.results')
	@include('welcome.sections.testimonials')
	
@endsection

@push('scripts')
<script>
  var swiper = new Swiper('.swiper-container', {
      slidesPerView: 1,
      width: 326,
      initialSlide: $('.swiper-slide').length/2,
      spaceBetween: 30,
      centeredSlides: true,
      pagination: {
          el: '.swiper-pagination',
          clickable: true,
      },
      navigation: {
          nextEl: '.swiper-button-next',
          prevEl: '.swiper-button-prev',
      },
  });
</script>

<script type="text/javascript">
let $scrollMark = $('#screens-composition').offset().top;

$(window).scroll(function() {
  let $scrollTop = $(this).scrollTop();

  if ($scrollTop > $scrollMark){
    $('#screens-container img:nth-child(2)').css('margin-left', '192px');
    $('#screens-container img:nth-child(3)').css('margin-left', '-192px');
    $('#screens-container img:nth-child(4)').css('margin-left', '328px');
    $('#screens-container img:nth-child(5)').css('margin-left', '-328px');
  } else {
    $('#screens-container img').css('margin-left', '0');
  }
});

</script>
@endpush