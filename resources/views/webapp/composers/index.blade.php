@extends('webapp.layouts.app')

@push('header')
@endpush

@section('content')
@include('webapp.layouts.header', ['title' => 'Composers', 'subtitle' => 'Explore our list of composers'])

<section>
	<div class="list-group list-group-flush">
		@foreach($composers as $composer)
			<a href="{{route('webapp.search.results', ['search' => $composer->name])}}" class="list-group-item list-group-item-action">{{$composer->reverse_name}}</a>
		@endforeach
	</div>
</section>
@endsection

@push('scripts')
<script type="text/javascript">
</script>
@endpush