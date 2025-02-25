@extends('webapp.layouts.app')

@section('content')
@include('webapp.layouts.header', ['title' => 'Hello ' . auth()->user()->first_name])

<section>
	<div class="row">
	  <div class="col-lg-4 col-md-4 col-sm-6 col-12 mb-4">
		  @include('users.profile.menu')
	  </div>
	  <div class="col-lg-8 col-md-8 col-sm-6 col-12">
  		<div class="tab-content" id="nav-tabContent">
  			@include('users.profile.tabs.profile')
  			@include('users.profile.tabs.preferences')
  			@include('users.profile.tabs.delete')
  		</div>
	  </div>
	</div>
</section>
@endsection
