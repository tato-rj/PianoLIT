@unless($internal ?? false)
Visit us on youtube: {{ config('services.channels.youtube') }}
@endunless

{{ $footerReason }}

@unless($internal ?? false)
Questions? Just reply, or say hello at contact@pianolit.com.

{{ $unsubscribeLabel ?? 'Unsubscribe / email preferences' }}: {{ $unsubscribeUrl }}
Privacy: {{ $websiteUrl.route('privacy', [], false) }}
Help: mailto:contact@pianolit.com
Forgot password: {{ $websiteUrl.route('password.request', [], false) }}
@endunless

© {{ date('Y') }} PianoLIT
{{ $websiteUrl }}
