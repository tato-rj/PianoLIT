<div class="border-y bg-white p-2 text-center mb-3">
	<div>
			@fa(['icon' => 'hourglass-half', 'color' => 'warning'])Your free download will be available in <strong>{{auth()->user()->membership->source->renews_at->diffForHumans()}}</strong>
	</div>
</div>