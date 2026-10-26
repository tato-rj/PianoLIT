YouTube: {{ config('services.channels.youtube') }}

{{ $footerReason }}

Questions? Just reply, or say hello at contact@pianolit.com.

{{ $unsubscribeLabel ?? 'Unsubscribe / email preferences' }}: {{ $unsubscribeUrl }}
Privacy: {{ $websiteUrl.route('privacy', [], false) }}
Help: mailto:contact@pianolit.com
Forgot password: {{ $websiteUrl.route('password.request', [], false) }}

© {{ date('Y') }} PianoLIT
{{ $websiteUrl }}
