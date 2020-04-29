@extends('webapp.layouts.app')

@push('header')
@endpush

@section('content')
@include('webapp.layouts.header')

@include('webapp.piece.options.header')

<section class="row mt-5">
	<div class="col-lg-6 col-md-6 col-12 mb-4">
		<h5 class="mb-3">Learn more</h5>
		<ul class="list-style-none p-0">
			<li class="mb-1"><strong>Composer:</strong> {{$piece->composer->name}}</li>
			<li class="mb-1"><strong>Key:</strong> {{$piece->key}}</li>
			<li class="mb-1"><strong>Period:</strong> {{$piece->period_name}}</li>
			<li class="mb-1"><strong>Level:</strong> {{ucfirst($piece->extended_level_name)}}</li>
			<li class="mb-1"><strong>From:</strong> {{$piece->collection}}</li>
		</ul>
	</div>
	<div class="col-lg-6 col-md-6 col-12 mb-4">
		<h5 class="mb-3">Ranking</h5>
		@foreach($piece->rankings as $ranking => $label)
		<div class="d-flex align-items-center mb-2">
			<img class="mr-2" style="width: 40px" src="{{asset('images/webapp/icons/'.$ranking.'.png')}}">
			<div class="text-nowrap">{{$label}}</div>
		</div>
		@endforeach
	</div>
</section>

@if($piece->curiosity)
<section class="row mb-5">
	<div class="col-lg-6 col-md-8 col-12 mx-auto">
		<div class="bg-light rounded p-4 text-center w-100">
			<h5 class="text-blue">Did you know?</h5>
			<p class="text-blue">{{$piece->curiosity}}</p>
			{{-- <a href="" class="btn rounded-pill btn-blue btn-wide">SHARE</a> --}}
		</div>
	</div>
</section>
@endif

@if(count($siblings) > 0)
<section class="row">
	<div class="col-12">
		<h5 class="mb-3">More from {{$piece->collection}}</h5>
		@each('webapp.components.piece', $siblings->each->isFavorited(auth()->user()->id), 'piece')
	</div>
</section>
@endif

@include('webapp.piece.components.panel')
@endsection

@push('scripts')
<script type="text/javascript">

</script>
@endpush