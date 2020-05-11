<div class="tab-pane fade" id="list-cancel">
	<p>This will cancel your membership and <u>cannot be reversed</u>. Once canceled, you will no longer be able to resume your current membership.</p>
	<form method="POST" action="{{route('webapp.membership.cancel')}}" id="update-collection-form" class="mb-1" disable-on-submit>
		@csrf
		<button type="submit" class="btn btn-block btn-danger">Cancel my membership</button>
	</form>
</div>