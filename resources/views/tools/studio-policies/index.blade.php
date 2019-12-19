@extends('layouts.app', [
	'title' => 'Studio Policy Generator | ' . config('app.name'),
	'shareable' => [
		'keywords' => 'studio policy,private studio agreement,private teacher,music teacher policy',
		'title' => 'Studio Policy Generator',
		'description' => 'Generate your studio policy in just a few seconds!',
		'thumbnail' => asset('images/misc/thumbnails/staff.jpg'),
		'created_at' => carbon('13-12-2019'),
		'updated_at' => carbon('13-12-2019')
	]])

@push('header')
<style type="text/css">

</style>
@endpush

@section('content')
@include('components.title', [
	'version' => '1.0',
	'title' => 'Studio Policy Generator', 
	'subtitle' => 'Generate your studio policy in just a few seconds!'])

<div class="container mb-4">
	<div class="row mb-6">
		<div class="col-lg-8 col-12 mx-auto mb-6">
			<div class="mb-4">
				<p>Are you looking to create a professionally looking policy for your studio? Do you want to dust off the one you have and improve it for your expanding studio? Then you're on the right place 🤗!</p>
				<p>Just click on the button below, answer a few questions and we'll generate a <u>well crafted</u> and <u>straight forward</u> document in just a few steps.</p>
			</div>
			<a href="{{route('users.studio-policies.create')}}" class="btn btn-primary shadow btn-block"><i class="fas fa-magic mr-2"></i>Create a Studio Policy now</a>
		</div>
		<div class="col-lg-8 col-12 mx-auto">			
			<div class="text-center">
				@auth
				<h6>Welcome back {{auth()->user()->first_name}}!</h6>
				@if(auth()->user()->studioPolicies()->exists())
					<h6><a href="{{route('users.studio-policies.index')}}" class="link-blue">Click here</a> to view the policies you've created</h6>
				@else
					<h6>It looks like you haven't created any policies for your studio yet. Click on the button above to get started.</h6>
				@endif
				@else
				<h6><a href="{{route('users.studio-policies.index')}}" id="auth-only" class="link-blue">Log in</a> to view the policies you've created</h6>
				@endauth
			</div>
		</div>
	</div>
	
	<div class="row mb-6">
		<div class="col-lg-8 col-12 mx-auto">
			<p><strong>Why do I need a studio policy?</strong></p>
			<p>An effective studio policy is one of the most important tools a piano teacher has for creating a private studio. Designing your piano studio policy gives you the opportunity to reflect on what you hope to accomplish in your studio and how you will handle the tricky situations that are certain to arise.</p>
			<p><a href="" class="link-blue">Click here</a> if you want to learn more.</p>
		</div>
	</div>
</div>

<div class="container mb-6">
	@include('components.sections.feedback')
	@include('components.sections.youtube')
</div>

@endsection

@push('scripts')
@include('components.addthis')

@endpush