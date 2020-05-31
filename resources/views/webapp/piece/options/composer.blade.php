@extends('webapp.layouts.app')

@push('header')
@endpush

@section('content')
@include('webapp.layouts.header')

@include('webapp.piece.options.header')

<section>
	<div>
		<div class="text-center mb-4">
			<img src="{{$piece->composer->cover_image}}" style="width: 160px" class="rounded-circle shadow mb-3">
			<h5 class="mb-0">{{$piece->composer->name}}</h5>
			<div class="mb-1"><small>{{$piece->composer->born_at}} - {{$piece->composer->died_at}}</small></div>
			<div>
				<span class="flag-icon flag-icon-{{$piece->composer->country->flag_code}} rounded-sm shadow-center mr-1"></span>
				<strong class="text-muted">{{$piece->composer->country->name}}</strong>
			</div>
		</div>
		<div class="mb-4">
				<div class="bg-light rounded p-4 text-center">
					<h5 class="text-blue">Did you know?</h5>
					<p class="text-blue">{{$piece->composer->curiosity}}</p>
				</div>
		</div>
		<div>
			<p style="white-space: pre-wrap;">{{$piece->composer->biography}}</p>
		</div>
	</div>
</section>

@include('webapp.piece.components.panel')
@endsection

@push('scripts')
<script type="text/javascript">

</script>
@endpush