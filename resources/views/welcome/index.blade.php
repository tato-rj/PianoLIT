@extends('layouts.app')

@push('header')
<style type="text/css">
.result-piece:hover > div {
  width: 228px!important;
}

#svg-searching {
  position:absolute;
  top:50%;
  left:50%;
  transform:translate(-50%, -50%);
  max-width:128px;
  max-height:128px;
}

.magnify{
  fill:#70cfa7;
  animation:search 1s infinite ease;
}
.doc{
  fill:#6c757d;
  animation:flyby 1s infinite ease;
}

@keyframes search {
  0%{
    transform:translate(40px, 40px) scale(.6);
  }
  50%{
    transform:translate(20px, 20px) scale(.6);
  }
  100%{
    transform:translate(40px, 40px) scale(.6);
  }
}

@keyframes flyby {
  0%{
    transform:translate(-20px, 20px) scale(.2);
    opacity:0
  }
  50%{
    transform:translate(30px, 20px) scale(.5);
    opacity:1
  }
  100%{
    transform:translate(100px, 20px) scale(.2);
    opacity:0
  }
}

#clock > div {
  width: 112px;
}

#clock .number {
  font-size: 2.6em;
  margin-bottom: 6px;
}

#clock .label {
  font-size: 1.2em;
  text-transform: uppercase;
}
</style>
@endpush

@section('content')

	@include('welcome.sections._lead')
	@include('welcome.sections.composition')
	@include('welcome.sections.tags')
	@include('components.overlays.tags')
	@include('welcome.sections.testimonials')

  @include('components.overlays.subscribe.paper-plane')
	
@endsection

@push('scripts')
<script src="{{asset('js/vendor/jquery.countdown.min.js')}}"></script>
<script type="text/javascript" src="https://cdn.rawgit.com/asvd/dragscroll/master/dragscroll.js"></script>
<script type="text/javascript">
$('.search-suggestion').click(function() {
  $('input[name="search"]').val($(this).text());
});

$(function() {
  var counter = 0;
  var isDragging = false;
  $(document)
  .on('mousedown', '.result-card', function(e) {
      $(window).mousemove(function() {
          isDragging = true;
          $(window).unbind("mousemove");
      });
  })
  .on('mouseup', '.result-card', function(element) {
      let card = element.target;
      var wasDragging = isDragging;
      isDragging = false;
      $(window).unbind("mousemove");
      if (!wasDragging) {
          $(card).closest('form').submit();
      }
  });
});

$('#show-more button').on('click', function() {
  let $button = $(this);
  let url = $button.attr('data-url');
  let tags = $('.result-row span.tag').textToArray();

  $button.prop('disabled', true).text('LOADING...');

  $.get(url, {tags: tags}, function(response) {
    if (response) {
      $(response).insertAfter($('.result-row').last());
      $button.prop('disabled', false).text('SHOW MORE');
    } else {
      $button.prop('disabled', false).text('NO MORE TAGS TO SHOW');      
    }
    dragscroll.reset();
  });
});
</script>

<script type="text/javascript">
$('#clock').countdown('2020/01/03', function(event) {
  var $this = $(this).html(event.strftime(''
    + '<div><span class="number">%w</span><div class="label">weeks</div></div> '
    + '<div><span class="number">%d</span><div class="label">days</div></div> '
    + '<div><span class="number">%H</span><div class="label">hours</div></div> '
    + '<div><span class="number">%M</span><div class="label">minutes</div></div> '
    + '<div><span class="number">%S</span><div class="label">seconds</div></div>'));
});
</script>

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
<script type="text/javascript">
$('#tags-search-button').on('click', function() {
  let url = $(this).attr('data-url');
  let $searching = $('#results-overlay .searching');
  let $positive = $('#results-overlay .positive-results');
  let $empty = $('#results-overlay .empty-results');
  let input = $('.selected-tag').text();

  $searching.show();
  $positive.hide();
  $empty.hide();
  
  $.get(url, {'search': input}, function(response) {
    setTimeout(function(){
      if (response.count > 0) {
        $positive.find('.results-count').text(response.count);
        $searching.hide();
        $positive.show();
      } else {
        $searching.hide();
        $empty.show();   
      }
    }, 2000);
  });
});

$("#subscribe-overlay").showAfter(5);
</script>
@endpush