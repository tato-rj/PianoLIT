<meta name="description" content="{{ $searchDescription }}">
<meta name="robots" content="max-image-preview:large">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:type" content="{{ $ogType ?? 'website' }}">
<meta property="og:title" content="{{ $searchTitle }}">
<meta property="og:description" content="{{ $searchDescription }}">
@if($canonical)
    <link rel="canonical" href="{{ $canonical }}">
    <meta property="og:url" content="{{ $canonical }}">
@endif
