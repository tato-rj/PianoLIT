@include('components.seo.page-head', ['siteName' => 'PianoLIT Learn'])

@php
    $image = isset($quiz) && $quiz->cover_path ? $quiz->cover_image() : null;
@endphp
<meta name="twitter:site" content="@litpiano">
<meta name="twitter:card" content="{{ $image ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $searchTitle }}">
<meta name="twitter:description" content="{{ $searchDescription }}">
@if($image)
    <meta property="og:image" content="{{ $image }}">
    <meta name="twitter:image" content="{{ $image }}">
@endif

@if($canonical)
    @php
        $items = [];
        foreach ($breadcrumbs as $breadcrumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => count($items) + 1,
                'name' => $breadcrumb['label'],
                'item' => $breadcrumb['url'] ?? $canonical,
            ];
        }
        $schema = [
            '@context' => 'https://schema.org',
            '@graph' => [['@type' => 'BreadcrumbList', 'itemListElement' => $items]],
        ];
        if (isset($quiz)) {
            $resource = [
                '@type' => 'LearningResource',
                'name' => $searchTitle,
                'url' => $canonical,
                'description' => $searchDescription,
                'learningResourceType' => 'Quiz',
                'inLanguage' => str_replace('_', '-', app()->getLocale()),
                'isAccessibleForFree' => true,
                'publisher' => ['@type' => 'Organization', 'name' => 'PianoLIT', 'url' => route('home')],
            ];
            if ($quiz->level) $resource['educationalLevel'] = ucfirst($quiz->level->name);
            if ($quiz->topics->isNotEmpty()) {
                $resource['about'] = $quiz->topics->map(fn ($topic) => ['@type' => 'Thing', 'name' => ucfirst($topic->name)])->values()->all();
            }
            if ($image) $resource['image'] = $image;
            $schema['@graph'][] = $resource;
        }
    @endphp
    <script type="application/ld+json">@json($schema)</script>
@endif
