<tr><td class="editorial-feature-row">
<table class="editorial-feature" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
<td class="editorial-feature-icon" width="56" valign="top"><img src="{{ $image }}" width="40" height="40" alt=""></td>
<td class="editorial-feature-copy" valign="top"><h3>{{ $title }}</h3>{{ Illuminate\Mail\Markdown::parse($slot) }}</td>
</tr></table>
</td></tr>
