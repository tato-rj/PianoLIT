@extends('layouts.app', ['title' => 'PianoLIT Crash Courses'])

@push('header')
@endpush

@section('content')
<section class="container mb-5">
    @pagetitle([
        'title' => 'Crash Courses', 
        'subtitle' => 'Learn with music lessons delivered daily right on your inbox'])

	@component('components.display.layout', [
		'collection' => $crashcourses,
		'topics' => $topics,
		'ads' => ['ebook', 'escore']
	])

	@slot('items')
		@each('crashcourses.course', $crashcourses, 'crashcourse')

		{{-- @each('crashcourses.course', $crashcourses, 'crashcourse') --}}
	@endslot
	
	@endcomponent
  </div>
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