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

	@slot('content')
		<div class="container">
			@each('crashcourses.course', $crashcourses, 'crashcourse')
		</div>
	@endslot
	
	@endcomponent
  </div>
</section>
@endsection

@push('scripts')
<script type="text/javascript">
</script>
@endpush