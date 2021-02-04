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
{{-- 	<div class="container">
		<div class="row">
			<div class="col-lg-8 col-md-10 col-12 mx-auto">
				@each('crashcourses.course', $crashcourses, 'crashcourse')
			</div>
		</div>
    </div> --}}
  </div>
</section>
@endsection

@push('scripts')
<script type="text/javascript">
</script>
@endpush