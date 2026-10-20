@php
    $splashVariant = ($admin ?? false) ? 'admin' : 'app';
    $splashProfiles = json_decode(file_get_contents(resource_path('data/brand-splash-screens.json')), true);
@endphp
@foreach($splashProfiles as $profile)
    @foreach(['portrait', 'landscape'] as $orientation)
        @php
            $splashWidth = $profile['width'] * $profile['scale'];
            $splashHeight = $profile['height'] * $profile['scale'];
            $splashPath = 'images/brand/splash/'.$splashVariant.'-'.($orientation === 'portrait' ? $splashWidth.'x'.$splashHeight : $splashHeight.'x'.$splashWidth).'.png';
        @endphp
<link rel="apple-touch-startup-image" href="{{ asset(mix($splashPath)) }}" media="screen and (device-width: {{ $profile['width'] }}px) and (device-height: {{ $profile['height'] }}px) and (-webkit-device-pixel-ratio: {{ $profile['scale'] }}) and (orientation: {{ $orientation }})">
    @endforeach
@endforeach
