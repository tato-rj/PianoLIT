<div class="d-flex mb-2 align-items-center">
	<div class="text-uppercase">@pill(['label' => $piece->extended_level_name, 'color' => $piece->level_name.'-raw'])</div>
	@fa(['icon' => 'lock', 'color' => 'grey', 'ml' => 2, 'if' => ! auth()->user()->isAuthorized()])
</div>