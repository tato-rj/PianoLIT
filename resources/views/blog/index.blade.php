@extends('layouts.app', ['title' => 'PianoLIT Blog'])

@section('content')
<section class="container mb-5">
    @pagetitle([
        'title' => 'Blog', 
        'subtitle' => 'A space where we share our ideas and explore intriguing facts about the exciting world of classical music'])

	
	@component('components.display.layout', [
		'collection' => $posts,
		'topics' => $topics])

	@slot('items')
		@each('blog.components.cards.large', $posts, 'post')
	@endslot
	
	@endcomponent
</section>

@endsection

@push('scripts')
<script type="text/javascript">
$('.card-title').clamp(2);
$('.card-text').each(function() {
  $(this).clamp(randomBetween(3, 5));
});
</script>
@endpush