@php($isMockup = !empty($mockup))
<tr><td class="welcome-benefit{{ $isMockup ? ' welcome-benefit-with-mockup' : '' }}" align="left">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
<td class="{{ $isMockup ? 'welcome-benefit-preview' : 'welcome-benefit-symbol' }}" width="{{ $isMockup ? 156 : 24 }}" valign="{{ $isMockup ? 'middle' : 'top' }}">
<img class="{{ $isMockup ? 'welcome-benefit-mockup' : 'welcome-benefit-icon' }}" src="{{ $image }}" width="{{ $isMockup ? 156 : 24 }}" height="{{ $isMockup ? 208 : 24 }}" alt="">
</td>
<td class="welcome-benefit-copy" valign="{{ $isMockup ? 'middle' : 'top' }}">
<h3>{{ $title }}</h3>
{{ Illuminate\Mail\Markdown::parse($slot) }}
</td></tr></table>
</td></tr>
