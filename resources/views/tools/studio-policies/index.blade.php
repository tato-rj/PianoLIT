@extends('layouts.app', [
	'title' => 'Studio Policy Generator | ' . config('app.name'),
	'shareable' => [
		'keywords' => 'studio policy,private studio agreement,private teacher,music teacher policy',
		'title' => 'Studio Policy Generator',
		'description' => 'Generate your studio policy in just a few seconds!',
		'thumbnail' => asset('images/misc/thumbnails/studio-policy.jpg'),
		'created_at' => carbon('13-12-2019'),
		'updated_at' => carbon('13-12-2019')
	]])

@push('header')
@endpush

@section('content')

@pagetitle([
	'version' => '1.0',
	'title' => 'Studio Policy Generator', 
	'subtitle' => 'Generate your studio policy in just a few seconds!'])

<div class="container mb-4">
	<div class="row mb-6">
		<div class="col-lg-8 col-12 mx-auto mb-6">
			<div class="mb-4">
				<p>Are you looking to create a professionally-looking policy for your studio? Do you want to dust off the one you have and improve it for your expanding studio? Then you're in the right place 🤗!</p>
				<p>Just click on the button below, answer a few questions and we'll generate a <u>well crafted</u> and <u>straight forward</u> document in just a few steps.</p>
			</div>
			<a href="{{route('users.studio-policies.create')}}" class="btn btn-primary shadow btn-block"><i class="fas fa-magic mr-2"></i>Create a Studio Policy now</a>
		</div>
		<div class="col-lg-8 col-12 mx-auto">			
			<div class="text-center">
				@auth
				<h6>Welcome back {{auth()->user()->first_name}}!</h6>
				@if(auth()->user()->studioPolicies()->exists())
					<h6><a href="{{route('users.studio-policies.index')}}">Click here</a> to view the policies you've created</h6>
				@else
					<div class="border rounded px-3 py-5 mt-4 text-center">
						<h2 class="text-grey mb-1"><i class="far fa-folder-open"></i></h2>
						<h5 class="text-grey m-0">You have not created a policy yet</h5>
					</div>
				@endif
				@else
				<h6><a href="{{route('users.studio-policies.index')}}" id="auth-only">Log in</a> to view the policies you've created</h6>
				@endauth
			</div>
		</div>
	</div>
	
	<div class="row mb-6">
		@include('tools.studio-policies.description')
	</div>
</div>

<div class="container mb-6">
	@include('components.sections.feedback')
	@include('components.sections.youtube')
</div>

@endsection

@push('scripts')
@addthis
@endpush