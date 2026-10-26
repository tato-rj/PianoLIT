@if($piece->cover_path)
<tr><td class="freepick-cover" background="{{ $websiteUrl.'/storage/'.ltrim($piece->cover_path, '/') }}" height="240" valign="top">
<!--[if mso]>
<a href="{{ $url }}"><img src="{{ $websiteUrl.'/storage/'.ltrim($piece->cover_path, '/') }}" width="600" height="240" alt="{{ $piece->medium_name }}"></a>
<![endif]-->
<!--[if !mso]><!-->
<table class="freepick-cover-frame" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
<tr><td class="freepick-cover-space" height="216"><a class="freepick-cover-link" href="{{ $url }}" title="{{ $piece->medium_name }}" aria-label="{{ $piece->medium_name }}">&nbsp;</a></td></tr>
<tr><td class="freepick-cover-edge" height="24" bgcolor="#ffffff">&nbsp;</td></tr>
</table>
<!--<![endif]-->
</td></tr>
@endif
<tr><td>
<table class="freepick-panel{{ $piece->cover_path ? ' freepick-panel-with-cover' : '' }}" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff">
{{ $slot }}
</table>
</td></tr>
