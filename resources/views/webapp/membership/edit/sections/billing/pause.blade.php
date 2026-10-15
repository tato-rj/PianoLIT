<div class="tab-pane fade" id="list-pause">
	<p>
		@if(auth()->user()->membership->source->isPaused())
		Billing is <strong>paused</strong>. New invoices are voided and Premium media is unavailable while paused. Resume to restore billing on your existing billing calendar.
		@else
		Pause future payment collection and Premium media access. Your billing calendar keeps running; unused paid time is not carried forward. Resolve any open invoice before pausing.
		@endif
	</p>
	<form method="POST" action="{{route('webapp.membership.update.billing-status')}}" class="mb-1" id="update-collection-form" disable-on-submit>
		@csrf
        <input type="hidden" name="operation_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
        <input type="hidden" name="paused" value="{{ $source->isPaused() ? '0' : '1' }}">

		@if(auth()->user()->membership->source->isPaused())
		<button type="submit" class="btn d-block w-100 btn-green">@icon('circle-play')Resume my membership</button>
		@else
		<button type="submit" class="btn d-block w-100 btn-warning">@icon('circle-pause')Pause my membership</button>
		@endif
	</form>
</div>