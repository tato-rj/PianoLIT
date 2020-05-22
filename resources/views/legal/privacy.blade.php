@extends('layouts.app', ['title' => 'Privacy Policy | ' . config('app.name')])

@section('content')
<div class="container mb-6">
	<div class="row">
		<div class="col-lg-8 col-md-10 col-12 mx-auto">
			@include('components.legal.privacy')
		</div>
	</div>
</div>
@endsection