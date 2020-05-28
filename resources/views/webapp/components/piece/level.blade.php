<div class="d-flex d-apart">
	<div class="mb-2 text-uppercase">@pill(['label' => $piece->extended_level_name, 'color' => $piece->level_name.'-raw'])</div>
	@fa(['icon' => 'lock', 'color' => 'grey', 'if' => ! auth()->user()->isAuthorized()])
</div>