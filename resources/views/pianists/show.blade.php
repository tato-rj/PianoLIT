@extends('layouts.app', [
  'title' => $pianist->name . ' | ' . config('app.name'),
  'popup' => 'gift',
  'shareable' => [
    'keywords' => $pianist->name . ',pianists,classical music,classical recordings,best classical pianists,chopin album,liszt recording,beethoven album,mozart music',
    'title' => 'Great Pianists | ' . $pianist->name,
    'description' => 'Discover the greatest pianists of our time and their recordings, an online database powered by Apple Music',
    'thumbnail' => asset('images/misc/thumbnails/pianists.jpg'),
    'created_at' => carbon('16-09-2019'),
    'updated_at' => carbon('16-09-2019')
    ]])

@section('content')

<div class="text-center mb-2">
  <label class="mb-0 text-grey d-block"><small>Powered by</small></label>
  <img src="{{asset('images/icons/apple-music.svg')}}" style="opacity: 0.7;width: 59px;margin-top: -12px;">
</div>

<img src="{{storage($pianist->cover_path)}}" class="rounded-circle shadow mb-4 mx-auto d-block" style="width: 160px">

<div class="mb-4 text-center px-3">
  <h3 class="m-0">{{$pianist->name}}</h3>
  <div id="subtitle" class="text-muted">
    <div class="mb-3">
      <p class="m-0"><small>Born on {{$pianist->date_of_birth->toFormattedDateString()}}</small></p>
      @if($pianist->date_of_death)
      <p class="mb-0" style="margin-top: -6px;"><small>Died on {{$pianist->date_of_death->toFormattedDateString()}}</small></p>
      @endif
    </div>
  
    <div class="mx-auto" style="max-width: 860px">{{$pianist->biography}}</div>
  </div>
</div>

<div class="container mt-4 mb-5">
	<div id="api-results" data-itunes="{{$pianist->itunes_id}}" class="row">
		<div class="col-12"><h5 class="text-grey my-6 text-center">Loading...</h5></div>
	</div>
</div>

{{-- @popup(['view' => 'subscription']) --}}
@endsection

@push('scripts')
@addthis
<script type="text/javascript" src="{{asset('js/views/pianists.js')}}"></script>
<script type="text/javascript">
  $("#gift-overlay").showAfter(5);
</script>
@endpush