<div class="tab-pane fade" id="list-preferences" role="tabpanel" aria-labelledby="list-preferences-list">
	<div class="row">
		<div class="col-lg-8 col-md-10 col-12">
			@if(auth()->user()->subscription()->exists())
			<div class="mb-4">
				<h5>My preferences</h5>
				<p>Select below the email lists you want to be in.</p>
			</div>
			<table class="table table-borderless w-100">
				<tbody>
	              @foreach(\App\Subscription::lists() as $list)
					<tr>
						<td class="p-0 align-middle"><h6 class="m-0"><i class="fas fa-mail-bulk mr-2"></i>{{snake_str($list)}}</h6></td>
						<td class="p-0 text-center align-middle">@include('admin.components.toggle.subscription', ['list' => $list, 'subscription' => auth()->user()->subscription])</td>
					</tr>
					<tr>
						<td colspan="2" class="pt-0 pb-3 text-muted"><small>{{config('mail.lists.' . $list)}}</small></td>
					</tr>
	              @endforeach
				</tbody>
			</table>
			@else
			<div class="mb-4">
				<h5>My preferences</h5>
				<p>Looks like you are not subscribed to our email lists!</p>
			</div>
			<div class="alert alert-grey text-center border-0">
				<p>Would you like to subscribe now?</p>
				<form method="POST" action="{{route('subscriptions.store')}}">
					@csrf
					@include('components.form.subscription.hidden')
					<input type="hidden" name="email" value="{{auth()->user()->email}}">
					<button class="btn btn-primary shadow">Yes, subscribe me now</button>
				</form>
			</div>
			@endif
		</div>
	</div>
</div>