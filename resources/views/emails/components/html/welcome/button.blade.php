@if(($kind ?? null) === 'app-store')
<a class="welcome-button welcome-app-store" href="{{ $url }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $slot }}"><img src="{{ $image }}" width="155" height="46" alt="Download on the App Store"></a>
@else
<a class="welcome-button{{ ($kind ?? null) === 'secondary' ? ' btn-secondary' : '' }}{{ ($size ?? null) === 'sm' ? ' btn-sm' : '' }}" href="{{ $url }}" target="_blank" rel="noopener noreferrer">@if(!empty($icon))<img class="welcome-button-icon" src="{{ $icon }}" width="20" height="20" alt="" aria-hidden="true">@endif{{ $slot }}</a>
@endif
