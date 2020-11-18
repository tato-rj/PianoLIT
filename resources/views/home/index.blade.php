@extends('layouts.app', ['popup' => 'subscription'])

@push('header')
@endpush

@section('content')

@include('home.sections._lead')
@include('home.sections.promo')
@include('home.sections.tags')
@include('home.sections.composition')
@include('home.sections.testimonials')
	
{{-- @popup(['view' => 'subscription']) --}}
@endsection

@push('scripts')
<script type="text/javascript">
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
let scrollMark = $('#screens-composition').offset().top - 300;

$(window).scroll(function() {
  let scrollTop = $(this).scrollTop();

  if (scrollTop > scrollMark){
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
$('#tags-search .tag').on('click', function() {
  let $btn = $(this);
  let $container = $('#pieces-container');
  let $label = $('#pieces-label');

  $btn.disable().toggleClass('btn-teal');
  $container.parent().addClass('opacity-4');

  axios.get($container.attr('data-url'), {params: {
    'ids': $('#tags-search .btn-teal').attrToArray('data-id'), 
    'names': $('#tags-search .btn-teal').attrToArray('data-name')
  }}).then(function(response) {
      $container.html(response.data.view); 
      $container.parent().removeClass('opacity-4');
      $label.text(response.data.label);
    })
    .catch(function(error) {
      console.log(error);
    })
    .then(function() {
      $btn.enable();
    });
});
</script>

<script type="text/javascript">
$(document).ready(function() {
    let $suggestions = $('#query-suggestions');
    
    setInterval(function() {
        $suggestions.find('li').last().detach().prependTo($suggestions).fadeIn('slow');
        $suggestions.find('li:visible').last().hide();
    }, 4000);
});
</script>

<script type="text/javascript">
 $(function() {
    var isDragging = false;
    $('.search-card, .piece-card')
    .mousedown(function() {
        $(window).mousemove(function() {
            isDragging = true;
            $(window).unbind("mousemove");
        });
    })
    .mouseup(function() {
        var wasDragging = isDragging;
        isDragging = false;
        $(window).unbind("mousemove");
        if (!wasDragging) {
            search($(this));
        }
    });
  });

function search(element) {
	goTo(element.attr('data-url'));
}
</script>
@endpush