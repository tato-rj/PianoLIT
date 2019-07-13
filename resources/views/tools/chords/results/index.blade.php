<div class="row mb-4">
	<div class="col-3 mb-4">
		<label class="text-muted"><small>THE NOTES YOU GAVE US ARE</small></label>
		<h2 class="text-muted"><strong>{{implode(' ', $request['notes'])}}</strong></h2>
	</div>
	<div class="col-9 bg-light rounded px-4 py-3 mb-4">
		@include('tools.chords.results.' . $request['results']['type'])
	</div>
	<div class="col-12 p-0">
		<div class="text-right mb-6">
			<div id="reload" class="cursor-pointer">
				<strong><i class="fas fa-long-arrow-alt-left mr-2"></i>Start again</strong>
			</div>
		</div>
	</div>
</div>