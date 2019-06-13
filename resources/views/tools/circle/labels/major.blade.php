<div class="tab-pane fade show active" id="mode-major" role="tabpanel">
	<div>
		<div class="mb-4">
			@include('tools.circle.labels.title', ['title' => 'major key'])
			<h4 class="key-name"></h4>
		</div>
		<div class="mb-4">
			@include('tools.circle.labels.title', ['title' => 'relative minor'])
			<h4 class="key-relative"></h4>
		</div>
		<div class="mb-4">
			@include('tools.circle.labels.title', ['title' => 'neighbors'])
			<div class="key-neighbors"></div>
		</div>
	</div>

	<div>
		@include('tools.circle.labels.title', ['title' => 'functional harmony'])
		<div class="mb-4 key-major-roman d-flex flex-wrap border bg-light rounded px-3 py-1"></div>
		<div class="row no-gutters">
			<div class="col-4 mb-4">
				<div class="">
					<label><small><strong>TONIC</strong></small></label>
					<div class="key-tonic"></div>
				</div>
			</div>
			<div class="col-4 mb-4">
				<div class="">
					<label><small><strong>DOMINANT</strong></small></label>
					<div class="key-dominant"></div>
				</div>
			</div>
			<div class="col-4 mb-4">
				<div class="">
					<label><small><strong>SUBDOMINANT</strong></small></label>
					<div class="key-subdominant"></div>
				</div>
			</div>
		</div>
	</div>

	<div>
		@include('tools.circle.labels.title', ['title' => 'negative harmony'])
		<div class="key-negative d-flex"></div>
	</div>
</div>