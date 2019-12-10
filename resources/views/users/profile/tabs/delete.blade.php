<div class="tab-pane fade" id="list-delete" role="tabpanel" aria-labelledby="list-delete-list">
	<div class="row">
		<div class="col-lg-8 col-md-10 col-12">
			<h5>Delete my account</h5>
			<p>If you wish to delete your account, just follow the steps below.</p>
			<p class="text-danger mb-4"><u>Important</u>: This action cannot be undone.</p>
			<a href="" data-name="{{auth()->user()->full_name}}" data-url="{{route('users.destroy', auth()->user()->id)}}" data-toggle="modal" data-target="#delete-modal" class="btn btn-block btn-danger">
				<strong><i class="fas fa-trash-alt mr-2"></i>I want to permanently delete my account</strong>
			</a>
		</div>
	</div>
</div>