<div class="tab-pane fade active show" id="list-card">
	<div class="alert alert-green">
		@fa(['fa_type' => 'b', 'icon' => 'cc-' . strtolower(auth()->user()->membership->source->card_brand)]){!! auth()->user()->membership->source->card() !!}
	</div>
	<p>Use the form below to update your card</p>
	<form action="{{route('webapp.membership.update.card')}}" method="POST" id="update-card-form" class="mb-3">
		@csrf
		<div class="mb-4">
			<div class="form-group">
				<div id="card-element" class="form-control"></div>
				<div id="card-errors" role="alert" class="invalid-feedback d-block"></div>
			</div>
		</div>
		<button id="card-button" class="btn btn-block btn-default mb-2">@fa(['icon' => 'lock'])Update my card</button>
		<p class="m-0"><small>Don't worry, your card information will never directly touch our servers.</small></p>
	</form>
</div>