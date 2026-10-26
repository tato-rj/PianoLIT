@php($withShadow = $shadow ?? true)
@php($logoImage = $withShadow ? mix('images/emails/welcome/floating-logo.png') : mix('images/emails/welcome/logo.png'))
<img class="editorial-logo {{ $withShadow ? 'welcome-logo' : 'editorial-logo-plain' }}" src="{{ $websiteUrl.$logoImage }}" width="{{ $withShadow ? 130 : 48 }}" height="{{ $withShadow ? 122 : 48 }}" alt="PianoLIT">
