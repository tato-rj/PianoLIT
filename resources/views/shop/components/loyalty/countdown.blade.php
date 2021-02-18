<div class="border-y bg-white p-2 text-center mb-3">
	<div>

			@fa(['icon' => 'hourglass-half', 'color' => 'warning'])Your next free download will be available in <strong>{{auth()->user()->loyaltyDiscounts()->latest()->first()->availableIn()}}</strong>
	</div>
</div>