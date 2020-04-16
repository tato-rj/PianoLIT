<div class="tab-pane fade" id="mode-minor" role="tabpanel">
	<div>
		<div class="row">
			<div class="col-6">
				<div class="mb-4">
					@include('tools.circle-of-fifths.labels.title', ['title' => 'key'])
					<h4 class="key-name"></h4>
				</div>
				<div class="mb-4">
					@include('tools.circle-of-fifths.labels.title', ['title' => 'relative key'])
					<h4 class="key-relative"></h4>
				</div>
			</div>
			<div class="col-6">
				@include('tools.circle-of-fifths.labels.title', ['title' => 'key signature'])
				<img class="key-signature w-100" data-folder="{{asset('images/misc/keys/')}}" src="{{asset('images/misc/keys/key-loading.svg')}}" style="max-width: 180px; margin-top: -8px;">
			</div>
		</div>
		<div class="mb-4">
			@include('tools.circle-of-fifths.labels.title', ['title' => 'closely related keys'])
			<div class="key-neighbors"></div>
		</div>
	</div>

	<div>
		@include('tools.circle-of-fifths.labels.title', ['title' => 'functional harmony'])
		<div class="mt-1 mb-2 key-minor-roman d-flex flex-wrap"></div>
		<div class="row no-gutters mb-4">
			<div class="col-4 mb-2">
				<div class="">
					<label class="mb-1"><small><strong>TONIC</strong></small></label>
					<div class="key-tonic"></div>
				</div>
			</div>
			<div class="col-4 mb-2">
				<div class="">
					<label class="mb-1"><small><strong>DOMINANT</strong></small></label>
					<div class="key-dominant"></div>
				</div>
			</div>
			<div class="col-4 mb-2">
				<div class="">
					<label class="mb-1"><small><strong>SUBDOMINANT</strong></small></label>
					<div class="key-subdominant"></div>
				</div>
			</div>
			<div class="col-12 text-muted">
				<small>* dominant chords must have the key's major 7th (the leading tone) in them, that is why we raise this note on minor keys and the V becomes major, the III augmented and the VII diminished.</small>
			</div>
		</div>
	</div>
</div>