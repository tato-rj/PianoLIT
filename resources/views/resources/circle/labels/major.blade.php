<div class="tab-pane fade show active" id="mode-major" role="tabpanel">
	<div>
		<div class="row">
			<div class="col-6">
				<div class="mb-4">
					@include('resources.circle.labels.title', ['title' => 'key'])
					<h4 class="key-name"></h4>
				</div>
				<div class="mb-4">
					@include('resources.circle.labels.title', ['title' => 'relative key'])
					<h4 class="key-relative"></h4>
				</div>
			</div>
			<div class="col-6">
				@include('resources.circle.labels.title', ['title' => 'key signature'])
				<img class="key-signature w-100" data-folder="{{asset('images/misc/keys/')}}" src="{{asset('images/misc/keys/key-loading.svg')}}" style="max-width: 180px; margin-top: -8px;">
			</div>
		</div>
		<div class="mb-4">
			@include('resources.circle.labels.title', ['title' => 'neighbor keys'])
			<div class="key-neighbors"></div>
		</div>
	</div>
	<div>
		@include('resources.circle.labels.title', ['title' => 'functional harmony'])
		<div class="mt-1 mb-3 key-major-roman d-flex"></div>
		<div class="row no-gutters">
			<div class="col-4 mb-4">
				<div class="">
					<label class="mb-1"><small><strong>TONIC</strong></small></label>
					<div class="key-tonic"></div>
				</div>
			</div>
			<div class="col-4 mb-4">
				<div class="">
					<label class="mb-1"><small><strong>DOMINANT</strong></small></label>
					<div class="key-dominant"></div>
				</div>
			</div>
			<div class="col-4 mb-4">
				<div class="">
					<label class="mb-1"><small><strong>SUBDOMINANT</strong></small></label>
					<div class="key-subdominant"></div>
				</div>
			</div>
		</div>
	</div>
</div>