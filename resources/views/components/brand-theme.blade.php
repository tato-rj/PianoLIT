@php
    $isAdminBrand = $admin ?? false;
    $iconPrefix = $isAdminBrand ? 'admin-' : '';
    $metadataPrefix = $isAdminBrand ? 'admin.' : '';
    $brandColor = $isAdminBrand ? '#ffffff' : '#0055fe';
@endphp
<link rel="icon" type="image/svg+xml" sizes="any" href="{{ asset(mix($isAdminBrand ? 'images/brand/admin-icon.svg' : 'images/brand/app-icon.svg')) }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset(mix($iconPrefix.'favicon-32x32.png')) }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset(mix($iconPrefix.'favicon-16x16.png')) }}">
<link rel="shortcut icon" href="{{ asset(mix($iconPrefix.'favicon.ico')) }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset(mix($iconPrefix.'apple-touch-icon.png')) }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="{{ $isAdminBrand ? 'PianoLIT Admin' : 'PianoLIT' }}">
@include('components.brand-splash', ['admin' => $isAdminBrand])
<link rel="manifest" href="{{ asset(mix($metadataPrefix.'site.webmanifest')) }}">
<link rel="mask-icon" href="{{ asset(mix($iconPrefix.'safari-pinned-tab.svg')) }}" color="{{ $isAdminBrand ? '#303a59' : $brandColor }}">
<meta name="msapplication-config" content="{{ asset(mix($metadataPrefix.'browserconfig.xml')) }}">
<meta name="msapplication-TileColor" content="{{ $brandColor }}">
<meta name="theme-color" content="{{ $brandColor }}">
