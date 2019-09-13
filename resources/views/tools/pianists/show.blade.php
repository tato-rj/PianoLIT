@extends('layouts.app', ['title' => 'Pianists | ' . config('app.name')])

@push('header')
<style type="text/css">
.fadeInUp {
	animation-duration: .2s;
}
</style>
@endpush

@section('content')

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
      data: {id: '{{$pianist->itunes_id}}', entity: 'album'},
      type: 'GET',
      crossDomain: true,
      dataType: 'jsonp',
      success: function(response) { 
      	let html = '<div class="col-12"><p class="text-center mb-4">We found <strong><span>'+response.resultCount+'</span> albums</strong> for this pianist</p></div>';
        let albums = response.results;
        albums.shift();

        for (album in albums) {
          html += `
          <div class="col-lg-6 col-md-6 col-12 mb-3">
			<a href="`+albums[album].collectionViewUrl+`" target="_blank" class="link-none">
				<div class="d-flex rounded p-2 alert-grey">
					<div class="mr-3"><img src="`+albums[album].artworkUrl100+`" style="width:100px; height: 100px;" class="rounded"></div>
					<div>
						<p class="m-0 album-title"><strong>`+albums[album].collectionName+`</strong></p>
						<p>Price: `+albums[album].collectionPrice+` `+albums[album].currency+`</p>
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