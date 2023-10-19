
<ul id="main-nav" class="nav nav-fill nav-tabs mb-3 position-relative border-bottom" style="border: 0; font-size: 96%;" role="tablist">
	<li class="nav-item">
		<a class="nav-link active" data-anchor="about" data-toggle="tab" href="#tab-about">About</a>
		<div class="nav-outline"></div>
		<div id="nav-border" class="t-2"></div>
	</li>
	<li class="nav-item">
		<a class="nav-link" data-anchor="score" data-toggle="tab" href="#tab-score">Score</a>
		<div class="nav-outline"></div>
	</li>
	<li class="nav-item">
		<a class="nav-link" data-anchor="tutorial" data-toggle="tab" href="#tab-tutorial" new-feature>Synthesia</a>
		<div class="nav-outline"></div>
	</li>

	@if(local() || auth()->user()->id === 284)
	<li class="nav-item">
		<a class="nav-link" data-anchor="lessons" data-toggle="tab" href="#tab-lessons">@fa(['icon' => 'hands-clapping', 'color' => 'orange', 'size' => 'lg'])Stage</a>
		<div class="nav-outline"></div>
	</li>
	@else
	<li class="nav-item">
		<a class="nav-link" data-anchor="lessons" data-toggle="tab" href="#tab-lessons">Lessons</a>
		<div class="nav-outline"></div>
	</li>
	@endif
</ul>