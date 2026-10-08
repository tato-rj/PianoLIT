@php
	$levelSystems = collect([
		['key' => 'rcm', 'label' => 'RCM', 'unit' => 'Level', 'title' => 'Equivalent to the RCM levels'],
		['key' => 'abrsm', 'label' => 'ABRSM', 'unit' => 'Level', 'title' => 'Equivalent to the ABRSM levels'],
		['key' => 'suzuki', 'label' => 'Suzuki', 'unit' => 'Book', 'title' => 'Equivalent to the Suzuki series'],
	])->map(function ($system) use ($rows) {
		$feedRow = collect($rows)->firstWhere('title', $system['title']);
		$system['cards'] = collect($feedRow['content'] ?? [])->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();
		return $system;
	});
	$firstLevelSystem = $levelSystems->first(function ($system) { return $system['cards']->isNotEmpty(); });
	$levelPalette = ['darkblue', 'lightblue', 'teal', 'green', 'yellow', 'orange', 'red'];
@endphp
@if($firstLevelSystem)
<section class="discover-levels mb-4" aria-labelledby="discover-levels-heading">
	<div class="discover-levels__header d-flex justify-content-between align-items-start gap-3 mb-3">
		<div>
			<h5 id="discover-levels-heading" class="mb-1">Find pieces at your level</h5>
			<p class="text-muted mb-0">Browse pieces of a similar difficulty.</p>
		</div>
		<div class="discover-levels__tabs tab-switch nav m-0" role="tablist" aria-label="Level system">
			@foreach($levelSystems as $system)
			<button class="{{ $system['key'] === $firstLevelSystem['key'] ? 'active' : '' }}" id="discover-levels-{{ $system['key'] }}-tab" data-bs-toggle="pill" data-bs-target="#discover-levels-{{ $system['key'] }}" type="button" role="tab" aria-controls="discover-levels-{{ $system['key'] }}" aria-selected="{{ $system['key'] === $firstLevelSystem['key'] ? 'true' : 'false' }}" tabindex="{{ $system['key'] === $firstLevelSystem['key'] ? '0' : '-1' }}" @if($system['cards']->isEmpty()) disabled aria-disabled="true" @endif>{{ $system['label'] }}</button>
			@endforeach
		</div>
	</div>
	<div class="tab-content">
		@foreach($levelSystems as $system)
		<div class="tab-pane {{ $system['key'] === $firstLevelSystem['key'] ? 'active' : '' }}" id="discover-levels-{{ $system['key'] }}" role="tabpanel" aria-labelledby="discover-levels-{{ $system['key'] }}-tab" tabindex="0">
			<div class="discover-levels__rail custom-scroll dragscroll dragscroll-horizontal card-scroll-row">
				<div class="d-flex gap-3 pb-2">
					@foreach($system['cards'] as $index => $card)
					@php
						$levelNumber = preg_replace('/^' . $system['key'] . '\s*(?:book|level)?\s*/i', '', $card->name);
						$palettePosition = $system['cards']->count() > 1 ? $index * (count($levelPalette) - 1) / ($system['cards']->count() - 1) : 0;
						$paletteIndex = (int) floor($palettePosition);
						$blend = $palettePosition - $paletteIndex;
						$startTones = gradient($levelPalette[$paletteIndex]);
						$endTones = gradient($levelPalette[min($paletteIndex + 1, count($levelPalette) - 1)]);
						$tones = [];
						foreach ([0, 1] as $endpoint) {
							$startRgb = sscanf(ltrim($startTones[$endpoint], '#'), '%2x%2x%2x');
							$endRgb = sscanf(ltrim($endTones[$endpoint], '#'), '%2x%2x%2x');
							$tones[] = sprintf('#%02X%02X%02X', ...array_map(function ($start, $end) use ($blend) { return (int) round($start + ($end - $start) * $blend); }, $startRgb, $endRgb));
						}
						$shade = implode(', ', sscanf(ltrim($tones[1], '#'), '%2x%2x%2x'));
					@endphp
					<a class="discover-level-card discover-piece-link link-none" href="{{ route('webapp.search.results', ['search' => $card->name]) }}" aria-label="{{ $system['label'] }} {{ $system['unit'] }} {{ $levelNumber }}, {{ $card->pieces_count }} {{ str_plural('piece', $card->pieces_count) }}" style="--level-tone: {{ $tones[0] }}; --level-shade: {{ $shade }};">
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
