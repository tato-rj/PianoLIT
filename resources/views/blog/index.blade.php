@extends('layouts.app', ['title' => 'PianoLIT Blog'])

@section('content')
<section class="container mb-5">
    @pagetitle([
        'title' => 'Blog', 
        'subtitle' => 'A space where we share our ideas and explore intriguing facts about the exciting world of classical music'])

	<div class="row mb-4">
		
		@each('blog.components.cards.large', $posts, 'post')

	</div>

  @pagination(['collection' => $posts])
</section>

@endsection

@push('scripts')
<script type="text/javascript">
$('.card-title').clamp(2);
$('.card-text').clamp(5);
</script>
@endpush