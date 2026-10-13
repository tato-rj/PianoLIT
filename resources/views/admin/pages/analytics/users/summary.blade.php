@if($summaryVisit->summary_parts)
<ul class="ua-summary-items" role="list">
    @foreach($summaryVisit->summary_parts as $part)
    <li class="ua-summary-item ua-summary-item--{{ $part['tone'] }}" title="{{ $part['description'] }}">@icon($part['icon'], ['mr' => 0])<span>{{ $part['label'] }}</span></li>
    @endforeach
</ul>
@else
<span class="ua-summary-empty">{{ $summaryVisit->summary }}</span>
@endif
