@extends('layouts.app', ['title' => 'PianoLIT Quizzes'])

@push('header')
  <!-- END Twitter Card -->
  <meta name="apple-itunes-app" content="app-id=00000000" />
@endpush

@section('content')
<section class="container mb-5">
    @include('components.title', [
        'title' => 'Quizzes', 
        'subtitle' => 'Test your knowledge and learn cool new facts about music'])

	<div class="row">
		
		@each('components.quiz.cards.large', $quizzes, 'quiz')

	</div>
</section>
@endsection

@push('scripts')
<script type="text/javascript">
$('.card-title').each(function() {
  $clamp(this, {clamp: 2});
});

$('.card-text').each(function() {
  $clamp(this, {clamp: 5});
});
</script>
@endpush