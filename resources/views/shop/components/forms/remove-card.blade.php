@component('components.modal', ['id' => 'remove-card', 'header' => 'Remove card on file'])
	@slot('body')
	<form method="POST" action="{{route('shop.payment-method.remove')}}">
		@csrf
		@method('DELETE')
		<p>Are you sure you want to remove your card <strong style="white-space: nowrap;">{!! auth()->user()->customer->card() !!}</strong>?</p>
		<button type="submit" class="btn btn-sm btn-danger">@fa(['icon' => 'trash-alt'])Yes, I'm sure</button>
	</form>
	@endslot
@endcomponent