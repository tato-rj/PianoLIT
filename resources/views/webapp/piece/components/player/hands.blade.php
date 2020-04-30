@if($piece->audio_lh && $piece->audio_rh)
<div class="d-flex align-items-center mr-3" id="select-hand">
	<button class="btn-raw text-muted opacity-4" data-target="#lh-player" title="Left hand only">
		<div>@fa(['size' => 'lg', 'icon' => 'hand-paper', 'classes' => 'mirror', 'mr' => 0])</div>
		<div style="display: none; font-size: 40%"><strong>LEFT HAND</strong></div>
	</button>
	<button class="btn-raw text-muted opacity-4" data-target="#rh-player" title="Right hand only">
		<div>@fa(['size' => 'lg', 'icon' => 'hand-paper', 'mr' => 0, 'ml' => 2])</div>
		<div style="display: none; font-size: 40%"><strong>RIGHT HAND</strong></div>
	</button>
</div>
@endif