@extends('layouts.app', [
  'title' => $pianist->name . ' | ' . config('app.name'),
  'shareable' => [
    'keywords' => $pianist->name . ',pianists,classical music,classical recordings,best classical pianists,chopin album,liszt recording,beethoven album,mozart music',
    'title' => 'Great Pianists | ' . $pianist->name,
    'description' => 'Discover the greatest pianists of our time and their recordings, an online database powered by Apple Music',
    'thumbnail' => asset('images/misc/thumbnails/pianists.jpg'),
    'created_at' => carbon('16-09-2019'),
    'updated_at' => carbon('16-09-2019')
    ]])

@push('header')
<style type="text/css">
.fadeInUp {
	animation-duration: .2s;
}
</style>
@endpush

@section('content')
@include('resources.pianists.powered')

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
	<div id="api-results" class="row">
		<div class="col-12"><h5 class="text-grey my-6 text-center">Loading...</h5></div>
	</div>
</div>

<div class="container mb-6">
	@include('components.sections.feedback')
	@include('components.sections.youtube')
</div>

@include('components.overlays.subscribe.model-2')
@endsection

@push('scripts')
@include('components.addthis')
<script type="text/javascript">
$("#subscribe-overlay").showAfter(5);

$(document).ready(function() {
  $.ajax({
      url: 'https://itunes.apple.com/lookup',
      data: {id: '{{$pianist->itunes_id}}', entity: 'album', limit: 200},
      type: 'GET',
      crossDomain: true,
      dataType: 'jsonp',
      success: function(response) { 
        let albums = response.results;
        albums.shift();
        let sentence = albums.length == 200 ?
          'We found more than <strong><span>'+albums.length+'</span> albums</strong>' : 
          'We found <strong><span>'+albums.length+'</span> albums</strong>';

      	let html = '<div class="col-12"><p class="text-center mb-4 text-muted">'+sentence+' on Apple Music</p></div>';

        for (album in albums) {
          html += `
          <div class="col-lg-6 col-md-6 col-12 mb-4 animate fadeInUp">
      			<a href="`+albums[album].collectionViewUrl+`" target="_blank" class="link-none">
      				<div class="d-flex rounded alert-grey border" style="height: 100px;border-color: #f3f5f7!important;">
      					<div class="mr-3"><img src="`+albums[album].artworkUrl100+`" style="width:100px; height: 100%;" class="rounded-left"></div>
      					<div class="d-flex flex-grow">
                  <div class="flex-grow py-2 pr-2">
        						<p class="m-0 album-title"><strong>`+albums[album].collectionName+`</strong></p>
        						<p>Price: `+albums[album].collectionPrice+` `+albums[album].currency+`</p>
                  </div>
                  <div class="d-flex flex-center p-2" style="background: rgba(0,0,0,0.025);">
                    <i class="ml-1 fas fa-angle-right fa-lg"></i>
                  </div>
      					</div>
      				</div>
      			</a>
          </div>
          `;
        }

        if (albums.length == 200)
          html += '<div class="col-12 mt-4 text-center"><div class="alert alert-warning d-inline-block"><i class="fas fa-exclamation-circle mr-2"></i>We reached Apple Music\'s limit of 200 results</div></div>';

        $('#api-results').html(html);

        $('.album-title').each(function() {
          $clamp(this, {clamp: 2});
        });
      },
      error: function(status) { alert('Couldn\'t get the albums from iTunes!'); }
  });
});
</script>
@endpush