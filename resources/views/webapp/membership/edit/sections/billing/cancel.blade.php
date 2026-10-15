<div class="tab-pane fade" id="list-cancel">
	<p>This will cancel your membership.</p>
	<p>You can still enjoy full access to your account until the end of your current billing cycle.</p>
	<form method="POST" action="{{route('webapp.membership.cancel')}}" id="cancel-membership-form" class="mb-1" disable-on-submit>
		@csrf
        <input type="hidden" name="operation_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
		<button type="submit" class="btn d-block w-100 btn-danger">Cancel my membership</button>
	</form>
</div>