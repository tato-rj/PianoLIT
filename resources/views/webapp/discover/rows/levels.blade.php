@php
	$levelSystems = \App\Services\Discover\Levels::groups($rows);
	$firstLevelSystem = $levelSystems->first(function ($system) { return $system['cards']->isNotEmpty(); });
	$levelPalette = ['rcm' => 'sky', 'abrsm' => 'mint', 'suzuki' => 'butter'];
@endphp
@if($firstLevelSystem)
<section class="discover-levels mb-4" aria-labelledby="discover-levels-heading">
	<div class="discover-levels__header d-flex justify-content-between align-items-start gap-3 mb-3">
		<div>
			<h5 id="discover-levels-heading" class="mb-1">Find pieces at your level</h5>
			<p class="text-muted mb-0">Similar difficulty to RCM, ABRSM or Suzuki books.</p>
		</div>
		<div class="discover-levels__tabs pill-filters nav" role="tablist" aria-label="Level system">
			@foreach($levelSystems as $system)
			<button class="nav-link {{ $system['key'] === $firstLevelSystem['key'] ? 'active' : '' }}" id="discover-levels-{{ $system['key'] }}-tab" data-bs-toggle="pill" data-bs-target="#discover-levels-{{ $system['key'] }}" type="button" role="tab" aria-controls="discover-levels-{{ $system['key'] }}" aria-selected="{{ $system['key'] === $firstLevelSystem['key'] ? 'true' : 'false' }}" tabindex="{{ $system['key'] === $firstLevelSystem['key'] ? '0' : '-1' }}" @if($system['cards']->isEmpty()) disabled aria-disabled="true" @endif>{{ $system['label'] }}</button>
			@endforeach
		</div>
	</div>
	<div class="tab-content">
		@foreach($levelSystems as $system)
		<div class="tab-pane {{ $system['key'] === $firstLevelSystem['key'] ? 'active' : '' }}" id="discover-levels-{{ $system['key'] }}" role="tabpanel" aria-labelledby="discover-levels-{{ $system['key'] }}-tab" tabindex="0">
			<div class="discover-levels__rail custom-scroll dragscroll dragscroll-horizontal card-scroll-row">
				<div class="d-flex gap-3 pb-2">
					@foreach($system['cards'] as $card)
					@php
						$levelColor = $levelPalette[$system['key']];
						$levelNumber = preg_replace('/^' . $system['key'] . '\s*(?:book|level)?\s*/i', '', $card->name);
					@endphp
					<a class="discover-level-card discover-piece-link link-none" href="{{ route('webapp.search.results', ['search' => $card->name]) }}" aria-label="{{ $system['label'] }} {{ $system['unit'] }} {{ $levelNumber }}, {{ $card->pieces_count }} {{ str_plural('piece', $card->pieces_count) }}" style="--level-tone: var(--pastel-{{ $levelColor }}-action); --level-surface: var(--pastel-{{ $levelColor }}-accent);">
						<div class="discover-level-card__art" aria-hidden="true">
							@include('webapp.discover.cards.level-shapes')
						</div>
						<div class="discover-level-card__copy d-flex align-items-center justify-content-between gap-2">
							<div>
								<p class="mb-1"><strong>{{ $system['unit'] }} {{ $levelNumber }}</strong></p>
								<p class="text-muted small mb-0">{{ $card->pieces_count }} {{ str_plural('piece', $card->pieces_count) }}</p>
							</div>
							@icon('chevron-right', ['mr' => 0, 'classes' => 'text-muted'])
						</div>
					</a>
					@endforeach
				</div>
			</div>
		</div>
		@endforeach
	</div>
</section>
@include('webapp.discover.rows.link-rail-script')
@endif
