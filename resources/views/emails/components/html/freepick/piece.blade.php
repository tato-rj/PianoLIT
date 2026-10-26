<tr><td class="freepick-piece welcome-padding">
<table class="freepick-identity" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
@if($piece->composer->cover_path)
<td class="freepick-portrait" width="80" valign="middle"><img src="{{ $websiteUrl.'/storage/'.ltrim($piece->composer->cover_path, '/') }}" width="80" height="80" alt="{{ $piece->composer->name }}"></td>
@endif
<td class="freepick-piece-copy" valign="middle">
@if($piece->level_name)
<p class="freepick-level"><span class="freepick-level-dot freepick-level-{{ $piece->level_name }}">●</span> {{ strtoupper($piece->extended_level_name) }}</p>
@endif
<h2><a href="{{ $url }}">{{ $piece->medium_name }}</a></h2>
<p class="freepick-composer">{{ $piece->attribution }}by {{ $piece->composer->name }}</p>
</td>
</tr></table>
</td></tr>
