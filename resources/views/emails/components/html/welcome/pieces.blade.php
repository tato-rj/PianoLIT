<tr><td class="welcome-section welcome-pieces welcome-padding{{ isset($variant) ? ' '.$variant : '' }}" align="center">
<h2>{{ $title }}</h2>
<div class="welcome-description">{{ Illuminate\Mail\Markdown::parse($slot) }}</div>
@if(count($pieces))
<table class="welcome-piece-list" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
@foreach($pieces as $piece)
<td class="welcome-piece" width="{{ 100 / count($pieces) }}%" valign="top" align="left">
<a href="{{ $piece['url'] }}" target="_blank" rel="noopener noreferrer"><img src="{{ $piece['image'] }}" width="160" alt="{{ $piece['title'] }}"><span class="welcome-piece-title">{{ $piece['title'] }}</span></a>
<p>{{ $piece['composer'] }}</p>
</td>
@endforeach
</tr></table>
@endif
@component('mail::welcome.button', ['url' => $url]){{ $button }}@endcomponent
</td></tr>
