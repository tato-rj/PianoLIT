
<ul id="main-nav" class="nav nav-fill nav-tabs position-relative border-bottom" style="border: 0; font-size: 96%; margin-bottom: 2rem;" role="tablist">
	<li class="nav-item">
		<a class="nav-link active" data-anchor="about" data-bs-toggle="tab" href="#tab-about">About</a>
		<div class="nav-outline"></div>
		<div id="nav-border" class="t-2"></div>
	</li>
	<li class="nav-item">
		<a class="nav-link" data-anchor="score" data-bs-toggle="tab" href="#tab-score">Score</a>
		<div class="nav-outline"></div>
	</li>
	<li class="nav-item">
		<a class="nav-link" data-anchor="synthesia" data-bs-toggle="tab" href="#tab-synthesia">Synthesia</a>
		<div class="nav-outline"></div>
	</li>
	@if($timeline->isNotEmpty())
	<li class="nav-item">
		<a class="nav-link" data-anchor="timeline" data-bs-toggle="tab" href="#tab-timeline" new-feature>Timeline</a>
		<div class="nav-outline"></div>
	</li>
	@endif

{{-- 	<li class="nav-item">
		<a class="nav-link" data-anchor="lessons" data-bs-toggle="tab" href="#tab-lessons">Lessons</a>
		<div class="nav-outline"></div>
	</li> --}}

</ul>