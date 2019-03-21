@extends('admin.layouts.app')

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Search',
    'description' => 'Explore the search api',
    'path' => 'pieces/search'])
    
    <div class="row my-3">
      <div class="col-lg-6 col-md-8 col-10 mx-auto">

        @include('admin.pages.search.input')

        @include('admin.pages.search.tags')
     
      </div>
    </div>
  </div>
</div>

@if(! empty($pieces) && request()->has('search'))
@component('admin.components.modals.results')
  @include('admin.pages.search.results')
@endcomponent
@endif

@include('admin.components.modals.delete', ['model' => 'piece'])

@endsection

@section('scripts')
<script type="text/javascript">
if ($('#results-modal').length > 0)
    $('#results-modal').modal('show');
</script>



<script type="text/javascript">
function getRandomInt(min, max) {
  return Math.floor(Math.random() * (max - min + 1) + min);
}

$('#tags-search .tag').on('click', function() {
  var tagsArray = [];
  $tag = $(this);

  if ($tag.hasClass('bg-light')) {
    $tag.addClass('random-pill-'+getRandomInt(1,4));
  } else {
    $tag.removeClass(function (index, className) {
      return (className.match (/(^|\s)random-pill-\S+/g) || []).join(' ');
    });
  }

  $tag.toggleClass('bg-light selected-tag');

  $('.selected-tag').each(function() {
    tagsArray.push($(this).text());
  });
  
  $('input#search').val(tagsArray.join(' ').replace(/\s+/g,' ').trim());
});
</script>

<script type="text/javascript">
$('.delete').on('click', function (e) {
  $piece = $(this);
  name = $piece.attr('data-name');
  url = $piece.attr('data-url');
  $('#delete-modal').search('form').attr('action', url);
})
</script>
@endsection
