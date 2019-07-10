<div class="row mb-4">
	<div class="col-4">
		<label class="text-muted"><small>THE NOTES YOU GAVE US ARE</small></label>
		<h1 class="text-teal"><strong>{{implode(' ', $request['notes'])}}</strong></h1>
	</div>
	<div class="col-8">
		<label class="text-muted"><small>WE'VE FOUND {{count($request['results']['content'])}} POSSIBLE {{strtoupper($request['results']['type'])}}</small></label>
		@include('tools.chords.results.' . $request['results']['type'])
	</div>
</div>
<div class="text-center mb-6">
	<button class="btn btn-primary" id="reset">Do it again!</button>
</div>