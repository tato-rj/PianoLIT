@extends('webapp.layouts.app')

@push('header')
@endpush

@section('content')
@include('webapp.layouts.header', ['title' => 'Settings', 'subtitle' => 'Control your profile and membership preferences'])

<div class="row">
	<div class="col-lg-8 col-12 mx-auto p-0 list-group">
	  <a href="#" class="list-group-item py-4 rounded-0 list-group-item-action">Subscribe @fa(['icon' => 'star', 'color' => 'yellow'])</a>
	  <a href="{{route('users.profile')}}" target="_blank" class="list-group-item py-4 rounded-0 list-group-item-action">My profile</a>
	  <a href="{{route('contact')}}" target="_blank" class="list-group-item py-4 rounded-0 list-group-item-action">Contact us</a>
	  <a href="{{config('app.url')}}" target="_blank" class="list-group-item py-4 rounded-0 list-group-item-action">Visit the website</a>
	  <a href="{{route('terms')}}" target="_blank" class="list-group-item py-4 rounded-0 list-group-item-action">Terms of service</a>
	  <a href="{{route('privacy')}}" target="_blank" class="list-group-item py-4 rounded-0 list-group-item-action">Privacy policy</a>
	  <a href="#" class="list-group-item py-4 rounded-0 list-group-item-action">Version 1.3.1</a>
	  <a href="" class="list-group-item py-4 rounded-0 list-group-item-action" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
		<form id="logout-form" action="{{ route('webapp.logout') }}" method="POST" style="display: none;">@csrf</form>Log out
	  </a>
	</div>
</div>

@endsection

@push('scripts')
@endpush