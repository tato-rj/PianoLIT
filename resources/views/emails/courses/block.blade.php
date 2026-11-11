<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:separate;">
<tr><td class="course-block" align="{{ $block['align'] }}" bgcolor="{{ $block['background'] }}" @if($block['background_image']) background="{{ $block['background_image'] }}" @endif style="padding:{{ $block['padding'] }}px;border-radius:{{ $block['radius'] }}px;color:{{ $block['color'] }};background-color:{{ $block['background'] }};text-align:{{ $block['align'] }};@if($block['background_image']) background-image:url('{{ $block['background_image'] }}');background-size:cover;background-position:center;@endif">
@if($block['type'] === 'divider')
<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td style="border-top:1px solid {{ $block['color'] }};font-size:0;line-height:0;">&nbsp;</td></tr></table>
@elseif($block['type'] === 'spacer')
<div style="height:{{ $block['height'] }}px;line-height:{{ $block['height'] }}px;font-size:1px;">&nbsp;</div>
@else
@if($block['image'] && in_array($block['type'], ['image', 'hero', 'panel']))
@if($block['type'] === 'image' && $block['url'])<a href="{{ $block['url'] }}">@endif
<img src="{{ $block['image'] }}" alt="{{ $block['alt'] }}" width="{{ min($block['image_width'], \App\Support\EmailLayout::WIDTH - 2 * \App\Support\EmailLayout::DESKTOP_INSET - 2 * $block['padding']) }}" style="display:block;width:100%;max-width:{{ $block['image_width'] }}px;height:auto;border:0;border-radius:{{ $block['radius'] }}px;margin:{{ $block['align'] === 'center' ? '0 auto' : ($block['align'] === 'right' ? '0 0 0 auto' : '0') }};">
@if($block['type'] === 'image' && $block['url'])</a>@endif
@endif
@if(in_array($block['type'], ['hero', 'panel']) && $block['title'])
<h2 style="font-family:inherit;font-size:{{ $block['type'] === 'hero' ? 32 : 22 }}px;line-height:1.25;margin:{{ $block['image'] ? '24px' : '0' }} 0 16px;color:{{ $block['color'] }};text-align:{{ $block['align'] }};">{{ $block['title'] }}</h2>
@endif
@if(in_array($block['type'], ['text', 'hero', 'panel']))
<div class="course-rich" style="color:{{ $block['color'] }};font-size:16px;line-height:1.65;text-align:{{ $block['align'] }};">{!! $block['content'] !!}</div>
@endif
@if($block['type'] === 'columns')
<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
<td class="course-column course-rich" width="50%" valign="top" style="color:{{ $block['color'] }};padding-right:12px;font-size:16px;line-height:1.65;">{!! $block['content'] !!}</td>
<td class="course-column course-rich" width="50%" valign="top" style="color:{{ $block['color'] }};padding-left:12px;font-size:16px;line-height:1.65;">{!! $block['content_right'] !!}</td>
</tr></table>
@endif
@if(in_array($block['type'], ['button', 'hero', 'panel']) && $block['url'] && $block['label'])
<table role="presentation" cellpadding="0" cellspacing="0" align="{{ $block['align'] }}" style="margin-top:{{ $block['type'] === 'button' ? 0 : 20 }}px;"><tr><td bgcolor="{{ $block['button_color'] }}" style="border-radius:4px;text-align:center;"><a href="{{ $block['url'] }}" style="display:inline-block;background-color:{{ $block['button_color'] }};border:1px solid {{ $block['button_color'] }};border-radius:4px;padding:12px 24px;min-height:20px;line-height:20px;font-family:Arial,Helvetica,sans-serif;font-size:14.4px;font-weight:400;letter-spacing:.4px;color:#ffffff;text-decoration:none;">{{ $block['label'] }}</a></td></tr></table>
@endif
@endif
</td></tr></table>
