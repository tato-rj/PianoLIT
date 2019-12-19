@extends('layouts.app', [
	'title' => 'Infographs | ' . config('app.name'),
	'shareable' => [
		'keywords' => 'infograph,learn music,music theory,music sheet,piano sheet,treble sheet,bass sheet',
		'title' => 'Infographs',
		'description' => 'Cool infographs about all music things related',
		'thumbnail' => asset('images/misc/thumbnails/infographs.jpg'),
		'created_at' => carbon('28-08-2019'),
		'updated_at' => carbon('28-08-2019')
	]])

@push('header')
@endpush

@section('content')

	<div class="container mb-6">
		<div class="row">
			<div class="col-lg-8 col-md-10 col-10 mx-auto">

				@include('components.return', ['url' => route('resources.infographs.index'), 'to' => 'Infographs page'])

				<div class="row">
					<div class="col-lg-6 col-md-6 col-sm-6 col-12">
						<img src="{{storage($infograph->thumbnail_path)}}" class="no-click preview border w-100">
					</div>
					<div class="col-lg-6 col-md-6 col-sm-6 col-12">
						<div class="">
							<div class="mb-4 pb-4 border-bottom">
								<div class="topics">
									@foreach($infograph->topics as $topic)
									<span class="badge badge-light mb-2 mr-2">{{$topic->name}}</span>
									@endforeach
								</div>
								<h4 class="mb-1">{{$infograph->name}}</h4>
								<p class="text-muted mb-4">{{$infograph->description}}</p>
								<div>
									<a id="{{auth()->check() ? 'infograph-download' : 'auth-only'}}" href="{{route('infographs.download', ['infograph' => $infograph->slug, 'size' => 'lg'])}}" class="btn btn-block btn-green py-2 font-weight-bold"><i class="fas fa-file-download mr-2"></i>Download</a>
								</div>
								@if($infograph->downloads > 4)
								<div class="text-muted text-center mt-1">
									<small><i class="fas fa-star text-yellow mr-1"></i>I've been downloaded <span>{{$infograph->downloads}}</span> times!</small>
								</div>
								@endif
							</div>
							<div style="line-height: 1.2" class="mb-3">
								<div><small><strong>Dimensions</strong></small></div>
								<div><small>This design is in A4 size paper 8.27 x 11.69 inches.</small></div>
								<div><small>The file format is JPG with 1800 x 2547 resolution.</small></div>
							</div>
							<div style="line-height: 1.2" class="mb-3">
								<div><small><strong>PianoLIT License</strong></small></div>
								<div><small>Free for personal and commercial purpose with attribution.</small></div>
							</div>
							<div style="line-height: 1.2">
								<div class="d-flex align-items-center">
									<div class="mr-2"><small><strong>Did you like this?</strong></small></div>
									<div class="d-flex align-items-center flex-nowrap">
										<button title="Love it!" data-value="1" data-url="{{route('infographs.update-score', $infograph->slug)}}" class="animate review border-0 bg-transparent text-grey px-1 mr-2"><small>Yes</small> <i class="fas fa-thumbs-up"></i></button>
										<button title="Not so much..." data-value="0" data-url="{{route('infographs.update-score', $infograph->slug)}}" class="animate review border-0 bg-transparent text-grey px-1">
											<small>No</small> <i class="fas fa-thumbs-down"></i></button>
									</div>
								</div>
								<div><small class="infograph-feedback">Help us improve by sending us your feedback.</small></div>
							</div>
						</div>
					</div>
				</div>
				@if(! $related->isEmpty())
				<div class="mt-5">
					<h5 class="mb-4">More like this</h5>
					<div class="row">
						@foreach($related as $infograph)
						@include('resources.infographs.card')
						@endforeach
					</div>
				</div>
				@endif
			</div>
		</div>
	</div>

<div class="container mb-6">
	@include('components.sections.feedback')
	@include('components.sections.youtube')
</div>
@endsection

@push('scripts')
@include('components.addthis')
<script>
$('.infograph-type-btn').on('click', function() {
	let $button = $(this);
	let type = $button.attr('data-target');

	$button.removeClass('btn-teal-outline').addClass('btn-teal');
	$button.siblings().addClass('btn-teal-outline').removeClass('btn-teal');

	$('.thumbnail').hide();
	$(type).show();

	$('input#search-infograph').val('');
});

$('.review').click(function() {vote($(this))});

function vote($hand) {
  if (! $hand.hasClass('voted')) {
    $('.review').addClass('voted');
    $.post($hand.attr('data-url'), {liked: $hand.attr('data-value')})
      .done(function(message) {
        $('.review').removeClass('text-grey').addClass('voted');
        $hand.siblings().addClass('text-light');
        $hand.addClass('text-blue');
        $('.infograph-feedback').text('Thanks for your feedback!');
      })
      .fail(function(status) {
        let feedback = status.status == 429 ? 'Sorry, you\'re voting too many times. Try again later!' : 'Sorry, we couldn\'t save your feedback.';
        $('.infograph-feedback').text(feedback);
      });
  }
}
</script>

@endpush