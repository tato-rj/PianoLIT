@extends('webapp.layouts.app')

@push('header')
@endpush

@section('content')
@include('webapp.layouts.header')

@include('webapp.piece.options.header')

<section>
	<div class="mb-4">
		<h4 class="mb-5 text-center">@fa(['icon' => 'apple', 'fa_type' => 'b'])Music recordings</h4>
		@if($piece->hasITunes())
			@foreach($piece->itunes_array as $itunes)
			@include('webapp.piece.components.apple-music', compact('piece'))
			@endforeach
		@else
			<div class="text-center text-grey">
				<h1>@fa(['icon' => 'itunes-note', 'fa_type' => 'b'])</h1>
				<p>No available recordings on @fa(['icon' => 'apple', 'fa_type' => 'b'])Music.</p>
			</div>
		@endif
	</div>
</section>

@include('webapp.piece.components.panel')
@endsection

@push('scripts')
<script type="text/javascript">

</script>
@endpush