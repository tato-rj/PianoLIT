@if($piece->cover_path)
<tr><td class="freepick-cover"><a href="{{ $url }}"><img src="{{ $websiteUrl.'/storage/'.ltrim($piece->cover_path, '/') }}" width="600" height="240" alt="{{ $piece->medium_name }}"></a></td></tr>
@endif
<tr><td>
<table class="freepick-panel{{ $piece->cover_path ? ' freepick-panel-overlap' : '' }}" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff">
{{ $slot }}
</table>
</td></tr>
