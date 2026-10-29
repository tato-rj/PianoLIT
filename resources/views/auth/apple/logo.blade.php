@if(!empty($markOnly))
{{-- Crop the empty border of the existing 90px asset to its visible mark. --}}
<svg width="{{ $logoSize ?? 24 }}" height="{{ $logoSize ?? 24 }}" viewBox="29 20 32 40" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">
  <image href="{{ asset('images/auth/apple-logo-'.(!empty($white) ? 'black' : 'white').'.png') }}" width="90" height="90"/>
</svg>
@else
<img src="{{ asset('images/auth/apple-logo-'.(!empty($white) ? 'black' : 'white').'.png') }}" width="24" height="24" alt="" aria-hidden="true">
@endif
