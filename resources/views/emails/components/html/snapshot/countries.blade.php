@component('mail::snapshot.panel', ['title' => $title, 'description' => $description])
@if(count($countries['rows']))
<table class="snapshot-country-list" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
@foreach($countries['rows'] as $country)
<tr><td><span class="snapshot-flag" aria-hidden="true">{{ $country['flag'] }}</span>{{ $country['name'] }}</td><td align="right" class="snapshot-country-number">{{ number_format($country['total']) }} <span class="snapshot-note">{{ number_format($country['share'], 1) }}%</span></td></tr>
@endforeach
</table>
@else
<p class="text-muted">No countries recorded for this week’s active accounts</p>
@endif
@if($countries['total'])<p class="snapshot-note snapshot-source-note">{{ number_format($countries['located']) }} of {{ number_format($countries['total']) }} have a saved country · {{ number_format($countries['unlocated']) }} unknown</p>@endif
@endcomponent
