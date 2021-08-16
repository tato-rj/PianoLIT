@extends('webapp.layouts.app', ['title' => 'Timeline'])

@push('header')
@endpush

@section('content')
@include('webapp.layouts.header')

@include('webapp.piece.options.header')

<h5 class="mb-3 text-center">Timeline</h5>

<section id="timeline">

	@each('webapp.piece.components.event', $timeline, 'event')

</section>

@include('webapp.piece.components.panel')
@endsection

@push('scripts')
<script type="text/javascript">
</script>
@endpush