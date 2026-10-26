@component('mail::snapshot.panel', ['title' => $title, 'description' => 'Distinct web app + iOS accounts'])
@if(count($growing))
<h3 class="snapshot-subheading">Gaining momentum</h3>
<table class="snapshot-growing-list" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
@foreach($growing as $piece)
<tr><td class="snapshot-growing-copy"><a href="{{ $piece['url'] ?? route('webapp.pieces.show', $piece['id']) }}" target="_blank" rel="noopener noreferrer">{{ $piece['title'] }}</a><p class="snapshot-note">{{ number_format($piece['previous']) }} → {{ number_format($piece['total']) }} accounts vs last week</p></td><td align="right" class="snapshot-growing-count"><span>+{{ number_format($piece['change']) }}</span></td></tr>
@endforeach
</table>
@endif
@if(count($pieces))
<h3 class="snapshot-subheading">Most explored</h3>
<table class="snapshot-piece-list" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
@foreach($pieces as $piece)
<tr>
<td class="snapshot-piece-art" width="72" valign="middle"><a href="{{ $piece['url'] ?? route('webapp.pieces.show', $piece['id']) }}" target="_blank" rel="noopener noreferrer"><img src="{{ isset($piece['image']) ? $websiteUrl.$piece['image'] : (!empty($piece['cover']) ? $websiteUrl.'/storage/'.ltrim($piece['cover'], '/') : $websiteUrl.mix('images/emails/welcome/premium-file-music.png')) }}" width="72" alt="{{ $piece['title'] }}"></a></td>
<td class="snapshot-piece-copy" valign="middle"><a href="{{ $piece['url'] ?? route('webapp.pieces.show', $piece['id']) }}" target="_blank" rel="noopener noreferrer">{{ $piece['title'] }}</a><p class="snapshot-note">{{ number_format($piece['web']) }} web · {{ number_format($piece['ios']) }} iOS</p></td>
<td class="snapshot-piece-count" align="right" valign="middle">{{ number_format($piece['total']) }}</td>
</tr>
@endforeach
</table>
@else
<p>No piece exploration recorded this week</p>
@endif
@endcomponent
