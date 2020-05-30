@extends('webapp.layouts.app')

@push('header')
@endpush

@section('content')
@include('webapp.layouts.header')

@include('webapp.piece.options.header')

<section>
	<div>
		<div class="text-center">
			<img src="{{$piece->composer->cover_image}}" style="width: 160px" class="rounded-circle shadow mb-3">
			<h5 class="mb-3">{{$piece->composer->name}}</h5>
		</div>
		<div class="mb-4">
				<ul class="list-style-none p-0">
					<li class="mb-1"><strong>Born:</strong> {{$piece->composer->born_at}}</li>
					<li class="mb-1"><strong>Died:</strong> {{$piece->composer->died_at}}</li>
					<li class="mb-1"><strong>Nationality:</strong> {{$piece->composer->nationality}}</li>
					<li class="mb-1"><strong>Period:</strong> {{$piece->composer->period}}</li>
				</ul>

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