@extends('layouts.app', ['title' => 'PianoLIT Blog'])

@push('header')
  <!-- END Twitter Card -->
  <meta name="apple-itunes-app" content="app-id=00000000" />
@endpush

@section('content')
<section class="container mb-5">
	<div class="row">
		
		@each('components.blog.cards.large', $posts, 'post')

	</div>
</section>

@include('components.overlays.subscribe.model-2')
@endsection

@push('scripts')
<script type="text/javascript">
$("#gift-overlay").showAfter(3);

$('.card-title').each(function() {
  $clamp(this, {clamp: 2});
});

$('.card-text').each(function() {
  $clamp(this, {clamp: 5});
});
</script>
@endpush