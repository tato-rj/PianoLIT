@extends('layouts.app', ['title' => 'PianoLIT Blog'])

@push('header')
  <!-- END Twitter Card -->
  <meta name="apple-itunes-app" content="app-id=00000000" />
@endpush

@section('content')
<section class="container mb-5">
    @include('components.title', [
        'title' => 'Blog', 
        'subtitle' => 'A space where we share our ideas and explore intriguing facts about the exciting world of classical music'])

	<div class="row mb-4">
		
		@each('components.blog.cards.large', $posts, 'post')

	</div>

  @pagination(['collection' => $posts])
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