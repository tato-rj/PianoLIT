<tr><td class="snapshot-section welcome-padding">
<table class="snapshot-members" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td class="snapshot-member-content">
<h2><a href="{{ $url }}">{{ $title }}</a></h2>
<p class="snapshot-member-engagement"><strong>{{ number_format($members['engaged']) }}</strong> of {{ number_format($members['total']) }} used PianoLIT</p>
<p class="snapshot-note">This week’s activity among members with access now</p>
<table class="snapshot-progress" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
@if($members['percent'])<td width="{{ $members['percent'] }}%" bgcolor="#0055fe" height="6" style="font-size: 0; line-height: 0;">&nbsp;</td>@endif
@if(($members['percent'] ?? 0) < 100)<td width="{{ 100 - ($members['percent'] ?? 0) }}%" bgcolor="#dfe7f5" height="6" style="font-size: 0; line-height: 0;">&nbsp;</td>@endif
</tr></table>
<table class="snapshot-member-stats" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
@foreach($labels as $key => $label)
<td width="33.33%" valign="top"><p class="snapshot-member-number">{{ number_format($members[$key]) }}</p><p>{{ $label }}</p></td>
@endforeach
</tr></table>
@if($members['review'])<p class="snapshot-review"><a href="{{ $url }}?status=needs_review">{{ number_format($members['review']) }} need review</a></p>@endif
<p class="snapshot-note">Stripe + Apple as of {{ $asOf }}<br>Active includes billing grace; trials and ending access are separate</p>
</td></tr></table>
</td></tr>
