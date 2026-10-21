@if(($kind ?? null) === 'app-store')
<a class="welcome-button welcome-app-store" href="{{ $url }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $slot }}"><img src="{{ $image }}" width="155" height="46" alt="Download on the App Store"></a>
@else
<a class="welcome-button" href="{{ $url }}" target="_blank" rel="noopener noreferrer">{{ $slot }}</a>
@endif
