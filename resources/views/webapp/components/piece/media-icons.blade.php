<ul class="piece-media-icons list-unstyled d-flex flex-wrap gap-3 text-muted m-0" aria-label="Available media">
	@if($piece->webapp_has_video ?? ($piece->tutorials_count > 0))
	<li>@icon('video', ['mr' => 0, 'title' => 'Video'])</li>
	@endif
	@if($piece->hasScore(true))
	<li>@icon('file-text', ['mr' => 0, 'title' => 'Score'])</li>
	@endif
	@if($piece->webapp_has_synthesia ?? $piece->hasTutorials(['synthesia']))
	<li>@icon('flame', ['mr' => 0, 'title' => 'Synthesia'])</li>
	@endif
</ul>
