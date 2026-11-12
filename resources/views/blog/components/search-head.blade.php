<meta name="description" content="{{ $searchDescription }}">
<meta name="robots" content="max-image-preview:large">
<meta property="og:site_name" content="PianoLIT Blog">
<meta property="og:type" content="{{ isset($article) ? 'article' : 'website' }}">
<meta property="og:title" content="{{ $searchTitle }}">
<meta property="og:description" content="{{ $searchDescription }}">
@if($canonical)
    <link rel="canonical" href="{{ $canonical }}">
    <meta property="og:url" content="{{ $canonical }}">
@endif

@isset($article)
    @php
        $published = $article->published_at->toIso8601String();
        $image = $article->cover_path ? $article->cover_image() : null;
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'mainEntityOfPage' => $canonical,
            'headline' => $searchTitle,
            'description' => $searchDescription,
            'datePublished' => $published,
            'author' => ['@type' => 'Organization', 'name' => 'PianoLIT Team'],
            'publisher' => ['@type' => 'Organization', 'name' => 'PianoLIT', 'url' => route('home')],
            'inLanguage' => str_replace('_', '-', app()->getLocale()),
        ];
        if ($image) $schema['image'] = $image;
    @endphp
    <meta property="article:published_time" content="{{ $published }}">
    <meta name="twitter:site" content="@litpiano">
    <meta name="twitter:title" content="{{ $searchTitle }}">
    <meta name="twitter:description" content="{{ $searchDescription }}">
    @if($image)
        <meta property="og:image" content="{{ $image }}">
        <meta name="twitter:image" content="{{ $image }}">
        <meta name="twitter:card" content="summary_large_image">
    @else
        <meta name="twitter:card" content="summary">
    @endif
    <script type="application/ld+json">@json($schema)</script>
@endisset
