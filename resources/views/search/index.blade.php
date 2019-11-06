@extends('layouts.app')

@push('header')
<style type="text/css">
</style>
@endpush

@section('content')

<section class="container">
  <div class="row mb-4">
    <div class="text-center col-12">
      @include('components.search.form', ['value' => request('search')])
      <p class="text-muted m-0">We found {{count($results)}} {{str_plural('result', count($results))}}!</p>
    </div>
  </div>
  <div class="row mb-7 no-gutters">
    @foreach($results as $model)
    <div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-2">
      @include('components.search.results.galleries.piece', ['width' => 'auto'])
    </div>
    @endforeach
  </div>
</section>
<section class="container mb-5">
  @include('components.sections.youtube') 
</section>
	
@endsection

@push('scripts')
<script type="text/javascript" src="https://cdn.rawgit.com/asvd/dragscroll/master/dragscroll.js"></script>
<script type="text/javascript">
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

</script>

@endpush