@extends('admin.layouts.app')

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Tour',
    'description' => 'Explore the tour with the api',
    'path' => 'pieces/tour'])
    
  </div>

  <div class="row">
    <div class="col-lg-6 col-md-8 col-10 mx-auto mb-5">

        <div id="app-intro" class="carousel slide" data-ride="carousel" data-interval="false">
        <form method="GET" action="{{route('admin.api.tour')}}">
          <input type="hidden" name="tour">
          <input type="hidden" name="search" value="">
        </form>
          <div class="carousel-inner">
            
              <div class="carousel-item row active carousel-level">
                <div class="col-8 mx-auto text-center my-2">
                  <div>
                    <h5 class="text-brand my-4">How long have you been playing piano?</h5>
                  </div>
                  <button class="tag-button btn btn-light py-3 border-bottom m-0 rounded-0 btn-block" data-tag="0"><strong>Just started</strong></button>
                  <button class="tag-button btn btn-light py-3 border-bottom m-0 rounded-0 btn-block" data-tag="1"><strong>Between one and three years</strong></button>
                  <button class="tag-button btn btn-light py-3 border-bottom m-0 rounded-0 btn-block" data-tag="2"><strong>More than three years</strong></button>
                  <button class="tag-button btn btn-light py-3 m-0 rounded-0 btn-block" data-tag="3"><strong>More than eight years</strong></button>
                </div>
              </div>
              <div class="carousel-item text-center carousel-level">
                <div>
                  <h5 class="text-brand my-4">How are your musical reading skills?</h5>
                </div>
                  <button class="tag-button btn btn-light py-3 border-bottom m-0 rounded-0 btn-block" data-tag="0"><strong>Can't really read either clef</strong></button>
                  <button class="tag-button btn btn-light py-3 border-bottom m-0 rounded-0 btn-block" data-tag="1"><strong>May be able to read treble or bass clef</strong></button>
                  <button class="tag-button btn btn-light py-3 border-bottom m-0 rounded-0 btn-block" data-tag="2"><strong>Can read treble and bass clefs together</strong></button>
                  <button class="tag-button btn btn-light py-3 m-0 rounded-0 btn-block" data-tag="3"><strong>Fully understand all musical notation</strong></button>
              </div>
              <div class="carousel-item row">
                <div class="col-8 mx-auto text-center my-2">
                  <div>
                    <h5 class="text-brand my-4">Which mood defines you most?</h5>
                  </div>
                  <button class="tag-button btn btn-light py-3 border-bottom m-0 rounded-0 btn-block" data-tag="playful"><strong>Playful</strong></button>
                  <button class="tag-button btn btn-light py-3 border-bottom m-0 rounded-0 btn-block" data-tag="dreamy"><strong>Dreamy</strong></button>
                  <button class="tag-button btn btn-light py-3 border-bottom m-0 rounded-0 btn-block" data-tag="elegant"><strong>Elegant</strong></button>
                  <button class="tag-button btn btn-light py-3 border-bottom m-0 rounded-0 btn-block" data-tag="crazy"><strong>Crazy</strong></button>
                  <button class="tag-button btn btn-light py-3 border-bottom m-0 rounded-0 btn-block" data-tag="melancholic"><strong>Melancholic</strong></button>
                  <button class="tag-button btn btn-light py-3 m-0 rounded-0 btn-block" data-tag="passionate"><strong>Passionate</strong></button>
                </div>
              </div>
              <div class="carousel-item text-center">
                <div>
                  <h5 class="text-brand my-4">If you were a composer you would be:</h5>
                </div>
                  <button class="tag-final tag-button btn btn-light py-3 border-bottom m-0 rounded-0 btn-block" data-tag="classical"><strong>Mozart</strong></button>
                  <button class="tag-final tag-button btn btn-light py-3 border-bottom m-0 rounded-0 btn-block" data-tag="baroque"><strong>Bach</strong></button>
                  <button class="tag-final tag-button btn btn-light py-3 border-bottom m-0 rounded-0 btn-block" data-tag="romantic"><strong>Beethoven</strong></button>
                  <button class="tag-final tag-button btn btn-light py-3 border-bottom m-0 rounded-0 btn-block" data-tag="romantic"><strong>Chopin</strong></button>
                  <button class="tag-final tag-button btn btn-light py-3 border-bottom m-0 rounded-0 btn-block" data-tag="romantic"><strong>Debussy</strong></button>
                  <button class="tag-final tag-button btn btn-light py-3 m-0 rounded-0 btn-block" data-tag="modern"><strong>Bartók</strong></button>
              </div>
{{--               <div class="carousel-item text-center carousel-level">
                <div>
                  <h5 class="text-brand my-4">What do you struggle with the most?</h5>
                </div>
                  <button class="tag-button tag-final btn btn-light py-3 border-bottom m-0 rounded-0 btn-block" data-tag="0"><strong>Not sure yet, I'm a beginner</strong></button>
                  <button class="tag-button tag-final btn btn-light py-3 border-bottom m-0 rounded-0 btn-block" data-tag="0"><strong>Memorization</strong></button>
                  <button class="tag-button tag-final btn btn-light py-3 border-bottom m-0 rounded-0 btn-block" data-tag="1"><strong>Little time for practicing</strong></button>
                  <button class="tag-button tag-final btn btn-light py-3 border-bottom m-0 rounded-0 btn-block" data-tag="3"><strong>Playing fast passages</strong></button>
                  <button class="tag-button tag-final btn btn-light py-3 border-bottom m-0 rounded-0 btn-block" data-tag="3"><strong>Sight reading</strong></button>
              </div> --}}
          </div>
        </div>
    </div>
  </div>
</div>

@if(! empty($pieces) && request()->has('search'))
@component('admin.components.modals.results')
  @include('admin.pages.search.results')
@endcomponent
@endif
@endsection

@section('scripts')
<script type="text/javascript">
if ($('#results-modal').length > 0)
    $('#results-modal').modal('show');
</script>
<script type="text/javascript">

function average(array) {
  let total = 0;
  for(var i = 0; i < array.length; i++) {
      total += parseInt(array[i]);
  }
  return Math.floor(total / array.length);
}

function prepareInput() {
  let tagsArray = [];
  $('#app-intro .carousel-item').slice(1).each(function() {
    tagsArray.push($(this).attr('value'));
  });
  $input.val(tagsArray.join(' '));
  // $input.val($input.val()+' '+tagsArray[Math.floor(Math.random()*tagsArray.length)]);
}

$('#app-intro .tag-button').on('click', function() {
  $tag = $(this);
  $form = $('#app-intro form');
  $input = $form.find('input[name="search"]');

  $tag.siblings('button').addClass('btn-light').removeClass('btn-default');
  $tag.addClass('btn-default').removeClass('btn-light');

  $tag.closest('.carousel-item').attr('value', $tag.attr('data-tag'));

  if ($tag.hasClass('tag-final')) {
    $input.val('');
    let levelNames = ['elementary', 'beginner', 'intermediate', 'advanced'];
    let levelsArray = [];
    
    $('#app-intro .carousel-level').each(function() {
      levelsArray.push($(this).attr('value'));
    });

    $('#app-intro .carousel-level').each(function() {
      $(this).attr('value', levelNames[average(levelsArray)]);
    });

    prepareInput();

    alert('Searching for: ' + $input.val());

    $('#app-intro form').submit();
  } else {
    $('#app-intro').carousel('next');
  }
});

</script>
@endsection
