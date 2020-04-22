@extends('webapp.layouts.app')

@push('header')
@endpush

@section('content')
@component('webapp.layouts.header', ['title' => 'Playlists', 'subtitle' => 'Explore the repertoire and discover new pieces every day'])
	<button class="btn btn-wide rounded-pill btn-outline-secondary">FOLLOW A PATH</button>
@endcomponent
<div class="row">
@foreach($playlists as $playlist)
<div class="mb-4 {{$loop->first ? 'col-12' : 'col-6'}}">
	<div class="bg-align-center rounded mb-3 position-relative" style="background-image: url({{$playlist->cover_image}}); height: {{$loop->first ? '25vh' : '20vh'}}">
		@pill(['label' => 'featured', 'color' => 'primary', 'text' => 'white', 'pos' => 'bottom-right', 'if' => $loop->first])
	</div>
	<div class="text-center">
		<h6 class="mb-1" style="line-height: 1; {{$loop->first ? 'font-size: 110' : 'font-size: 92%'}}">{{$playlist->name}}</h6>
		<p class="m-0" style="line-height: 1;"><small>{{$loop->first ? $playlist->subtitle : $playlist->pieces_count . ' pieces'}}</small></p>
	</div>
</div>
@endforeach
</div>
@endsection

@push('scripts')
@endpush