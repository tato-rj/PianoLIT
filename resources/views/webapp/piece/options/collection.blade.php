@extends('webapp.layouts.app', ['title' => $piece->collection])

@push('header')
@endpush

@section('content')
@include('webapp.layouts.header')

@include('webapp.piece.options.header')

<section class="row">
	<div class="col-12">
		<div class="text-center mb-4">
			<p class="mb-1">More from</p>
			<h5>{{$piece->collection}}</h5>
		</div>
		@foreach($siblings->each->isFavorited(auth()->user()->id) as $piece)
			@include('webapp.components.piece', compact('hasFullAccess'))
		@endforeach
	</div>
</section>

@include('webapp.piece.components.panel')
@endsection

@push('scripts')
<script type="text/javascript">

</script>
@endpush