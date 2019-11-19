@extends('layouts.app')

@push('header')
<style type="text/css">
</style>
@endpush

@section('content')

<section class="container">
  <div class="row mb-4">
    <div class="text-center col-12">
      @include('components.search.form')
      <p class="mb-1">Pieces that are similar to</p>
      <h5><strong>{{$piece->medium_name}}</strong> by {{$piece->composer->short_name}}</h5>
      @include('components.search.results.count')
    </div>
  </div>
  <div class="row mb-7 no-gutters">
    @forelse($results as $model)
    <div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-2">
      @include('components.search.results.galleries.piece', ['width' => 'auto'])
    </div>
    @empty
    <p>No results...</p>
    @endforelse
  </div>
</section>
<section class="container mb-5">
  @include('components.sections.youtube') 
</section>
	
@endsection

@push('scripts')
<script type="text/javascript" src="https://cdn.rawgit.com/asvd/dragscroll/master/dragscroll.js"></script>
<script type="text/javascript">
$('.result-card').click(function() {
  goTo($(this).attr('data-url'));
});
</script>

@endpush