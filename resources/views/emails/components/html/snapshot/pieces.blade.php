<tr><td class="snapshot-section welcome-padding">
<h2>{{ $title }}</h2>
@if(count($pieces))
<p class="snapshot-note snapshot-piece-intro">Most explored by distinct accounts</p>
<table class="snapshot-piece-list" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
@foreach($pieces as $piece)
<tr>
<td class="snapshot-rank" width="24" valign="middle">{{ $loop->iteration }}</td>
<td class="snapshot-piece-art" width="96" valign="middle"><a href="{{ $piece['url'] ?? route('webapp.pieces.show', $piece['id']) }}" target="_blank" rel="noopener noreferrer"><img src="{{ isset($piece['image']) ? $websiteUrl.$piece['image'] : (!empty($piece['cover']) ? $websiteUrl.'/storage/'.ltrim($piece['cover'], '/') : $websiteUrl.mix('images/emails/welcome/premium-file-music.png')) }}" width="96" alt="{{ $piece['title'] }}"></a></td>
<td class="snapshot-piece-copy" valign="middle"><a href="{{ $piece['url'] ?? route('webapp.pieces.show', $piece['id']) }}" target="_blank" rel="noopener noreferrer">{{ $piece['title'] }}</a><p class="snapshot-note">{{ number_format($piece['web']) }} web · {{ number_format($piece['ios']) }} iOS</p></td>
<td class="snapshot-piece-count" align="right" valign="middle">{{ number_format($piece['total']) }}</td>
</tr>
@endforeach
</table>
@else
<p>No piece exploration recorded this week</p>
@endif
</td></tr>
