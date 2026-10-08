@if($piece->extended_level_name)
<p class="piece-level small mt-2 mb-0">
	@icon('circle', ['mr' => 2, 'classes' => 'color-' . $piece->level_name, 'filled' => true])
	<span>{{ ucfirst($piece->extended_level_name) }}</span>
</p>
@endif
