@if($row['title'] === 'Recently viewed')
	@include('webapp.discover.rows.recently-viewed')
@elseif(\Illuminate\Support\Str::startsWith($row['title'], 'Pieces that are '))
	@include('webapp.discover.rows.recently-viewed', [
		'rowHeadingId' => 'pieces-by-mood-heading',
		'rowHeading' => ucfirst(($row['tag'] ?? null) ?: trim(\Illuminate\Support\Str::after($row['title'], 'Pieces that are '))) ?: 'By mood',
		'rowSubtitle' => 'Pieces by mood',
		'wrapPieceTitles' => true,
	])
@elseif($row['title'] === "Like today's free pick")
	@include('webapp.discover.rows.recently-viewed', [
		'rowHeadingId' => 'free-pick-similar-heading',
		'rowHeading' => "Like this week's free pick",
		'wrapPieceTitles' => true,
	])
@elseif($row['title'] === 'Latest pieces')
	@include('webapp.discover.rows.latest')
@elseif($row['title'] === 'For you')
	@include('webapp.discover.rows.for-you')
@elseif($row['title'] === 'From women composers')
	@include('webapp.discover.rows.women-composers')
@elseif($row['title'] === 'From black composers')
	@include('webapp.discover.rows.composer-feature', ['featureKey' => 'black'])
@else
<div class="mb-4">
	<h5 class="mb-3">{{$row['title']}}</h5>
	<div class="custom-scroll dragscroll dragscroll-horizontal card-scroll-row">
		<div class="d-flex pb-2" style="height: 144px;">
			@foreach($row['content'] as $card)
				@include('webapp.discover.cards.' . $row['type'], compact('hasFullAccess'))
			@endforeach
		</div>
	</div>
</div>
@endif
