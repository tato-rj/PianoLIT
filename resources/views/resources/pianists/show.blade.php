@extends('layouts.app', ['title' => 'Pianists | ' . config('app.name')])

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
@include('components.title', [
	'title' => $pianist->name, 
	'subtitle' => $pianist->biography,
	'width' => '800px'])

<div class="container mt-4 mb-5">
	<div id="api-results" class="row">
		<div class="col-12"><h5 class="text-grey my-6 text-center">Loading...</h5></div>
	</div>
</div>

<div class="container mb-6">
	@include('components.sections.feedback')
	@include('components.sections.youtube')
</div>

@endsection

@push('scripts')
@include('components.addthis')
<script type="text/javascript">
$(document).ready(function() {
  $.ajax({
      url: 'https://itunes.apple.com/lookup',
      data: {id: '{{$pianist->itunes_id}}', entity: 'album', limit: 200},
      type: 'GET',
      crossDomain: true,
      dataType: 'jsonp',
      success: function(response) { 
        let albums = response.results;
      	let html = '<div class="col-12"><p class="text-center mb-4 text-muted">We found <strong><span>'+albums.count+'</span> albums</strong> on Apple Music</p></div>';
        albums.shift();

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