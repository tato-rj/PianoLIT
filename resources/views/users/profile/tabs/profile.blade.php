<div class="tab-pane fade show active" id="list-profile" role="tabpanel" aria-labelledby="list-profile-list">
	<div>
			<h5>My profile information</h5>
			<p>Use the form below to edit your profile.</p>
			<form method="POST" action="{{route('users.update', auth()->user()->id)}}">
				@csrf
				@method('PATCH')
				<div class="form-row">
					<div class="col-lg-6 col-md-6 col-12">
						@input(['label' => 'First name', 'bag' => 'default', 'name' => 'first_name', 'limit' => 200, 'value' => auth()->user()->first_name])
					</div>
					<div class="col-lg-6 col-md-6 col-12">
						@input(['label' => 'Last name', 'bag' => 'default', 'name' => 'last_name', 'limit' => 200, 'value' => auth()->user()->last_name])
					</div>
				</div>

				@input(['label' => 'Email', 'bag' => 'default', 'name' => 'email', 'limit' => 200, 'value' => auth()->user()->email, 'type' => 'email'])

				@input(['label' => 'Change your password', 'placeholder' => 'New password', 'bag' => 'default', 'name' => 'password', 'type' => 'password', 'required' => 'no'])
				@input(['placeholder' => 'Confirm your password', 'bag' => 'default', 'name' => 'password_confirmation', 'type' => 'password', 'required' => 'no'])

				<div class="form-group">
					<button class="btn btn-primary shadow btn-wide">@fa(['icon' => 'save'])Save my changes</button>
				</div>
			</form>

	</div>
</div>