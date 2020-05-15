@extends('webapp.layouts.app')

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
		@each('webapp.components.piece', $siblings->each->isFavorited(auth()->user()->id), 'piece')
	</div>
</section>

@include('webapp.piece.components.panel')
@endsection

@push('scripts')
<script type="text/javascript">

</script>
@endpush