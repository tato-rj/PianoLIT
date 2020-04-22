@extends('webapp.layouts.app')

@push('header')
@endpush

@section('content')
@include('webapp.layouts.header', ['title' => 'Settings', 'subtitle' => 'Control your profile and membership preferences'])

<div class="row">
	<div class="col-lg-8 col-12 mx-auto p-0 list-group">
	  <a href="#" class="list-group-item py-4 rounded-0 list-group-item-action">Subscribe @fa(['icon' => 'star', 'color' => 'yellow'])</a>
	  <a href="#" class="list-group-item py-4 rounded-0 list-group-item-action">My profile</a>
	  <a href="#" class="list-group-item py-4 rounded-0 list-group-item-action">Contact us</a>
	  <a href="{{route('home')}}" target="_blank" class="list-group-item py-4 rounded-0 list-group-item-action">Visit the website</a>
	  <a href="#" class="list-group-item py-4 rounded-0 list-group-item-action">Terms of service</a>
	  <a href="#" class="list-group-item py-4 rounded-0 list-group-item-action">Privacy policy</a>
	  <a href="#" class="list-group-item py-4 rounded-0 list-group-item-action">Version 1.3.1</a>
	  <a href="#" class="list-group-item py-4 rounded-0 list-group-item-action">Log out</a>
	</div>
</div>

@endsection

@push('scripts')
@endpush