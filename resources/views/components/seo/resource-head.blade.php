@include('components.seo.page-head', ['siteName' => $siteName ?? 'PianoLIT Learn'])

<meta name="twitter:card" content="{{ !empty($searchImage) ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $searchTitle }}">
<meta name="twitter:description" content="{{ $searchDescription }}">
@if(!empty($searchImage))
    <meta property="og:image" content="{{ $searchImage }}">
    <meta name="twitter:image" content="{{ $searchImage }}">
@endif

@if($canonical)
    @php
        $graph = [];
        if (!empty($breadcrumbs)) {
            $items = [];
            foreach ($breadcrumbs as $breadcrumb) {
                $items[] = [
                    '@type' => 'ListItem',
                    'position' => count($items) + 1,
                    'name' => $breadcrumb['label'],
                    'item' => $breadcrumb['url'] ?? $canonical,
                ];
            }
            $graph[] = ['@type' => 'BreadcrumbList', 'itemListElement' => $items];
        }
        if (isset($searchEntity)) $graph[] = $searchEntity;
        $schema = ['@context' => 'https://schema.org', '@graph' => $graph];
    @endphp
    @if($graph)
        <script type="application/ld+json">@json($schema)</script>
    @endif
@endif
