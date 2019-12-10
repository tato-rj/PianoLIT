<div class="tab-pane fade show active" id="list-profile" role="tabpanel" aria-labelledby="list-profile-list">
	<div class="row">
		<div class="col-lg-8 col-md-10 col-12">
			<h5>My profile information</h5>
			<p>Use the form below to edit your profile.</p>
			<form method="POST" action="{{route('users.update', auth()->user()->id)}}">
				@csrf
				@method('PATCH')
				<div class="form-row">
					<div class="col-lg-6 col-md-6 col-12">
						@include('components.form.input', ['label' => 'First name', 'bag' => 'default', 'name' => 'first_name', 'limit' => 200, 'value' => auth()->user()->first_name])
					</div>
					<div class="col-lg-6 col-md-6 col-12">
						@include('components.form.input', ['label' => 'Last name', 'bag' => 'default', 'name' => 'last_name', 'limit' => 200, 'value' => auth()->user()->last_name])
					</div>
				</div>
				<div class="form-group">
					@include('components.form.input', ['label' => 'Email', 'bag' => 'default', 'name' => 'email', 'limit' => 200, 'value' => auth()->user()->email, 'type' => 'email'])
				</div>
				<div class="form-group text-center">
					<a href="{{ route('password.request') }}" class="link-blue"><strong>I want to change my password</strong></a>
				</div>
				<div class="form-group">
					<button class="btn btn-primary shadow btn-block">Save my changes</button>
				</div>
			</form>
		</div>
	</div>
</div>