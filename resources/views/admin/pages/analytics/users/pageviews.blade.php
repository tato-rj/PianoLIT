<h2 class="ua-section-title">Browser pageviews</h2>
<div class="ua-metrics ua-metrics-four mb-3">
    <article class="ua-card ua-metric"><span>Pageviews</span><strong>{{ number_format($pageViews['total']) }}</strong></article>
    @foreach($pageViews['focus'] as $project)
    <article class="ua-card ua-metric"><span>{{ $project->name }}</span><strong>{{ number_format($project->total) }}</strong></article>
    @endforeach
</div>
<p class="ua-note">Successful browser page openings recorded by the server, including repeat openings. Actions, AJAX requests, iOS requests and imported daily counters are excluded. Quizzes includes its directory and individual quiz pages, not completed attempts. Counts follow the selected dates, platforms and audience.</p>
<div class="ua-grid">
    <section class="ua-card">
        <h2>Pageviews by hub</h2>
        <p class="ua-meta">Content hubs include older URLs for the same resources. Select a hub to see its pages.</p>
        <div class="ua-table-scroll"><table class="ua-table">
            <thead><tr><th scope="col">Hub</th><th scope="col">Pageviews</th><th scope="col">Share</th></tr></thead>
            <tbody>@foreach($pageViews['hubs'] as $hub)
                <tr><td><a href="{{ request()->fullUrlWithQuery(['page_hub' => $hub->key, 'pages_page' => null]) }}#pageview-pages">{{ $hub->name }}</a></td><td>{{ number_format($hub->total) }}</td><td>{{ $pageViews['total'] ? number_format($hub->total / $pageViews['total'] * 100, 1) : '0.0' }}%</td></tr>
            @endforeach</tbody>
        </table></div>
    </section>
    <section class="ua-card">
        <h2>What these counts cover</h2>
        <p>Pageviews are counted when the page opened, including visits that started before this period. Hub totals and the three resource totals cover all matching pages, across every results page.</p>
        <p>Query strings, filters and URL fragments do not create separate pages. Older tool and resource URLs are combined with their current Learn pages.</p>
        <p>Names use the recorded route and public path. Catalogue titles use their current name when available. Missing page information stays unidentified.</p>
        @if($pageViews['first_view'])<p class="ua-meta">First matching pageview: {{ carbon($pageViews['first_view'])->format('M j, Y · g:i:s A') }}. Earlier tracking coverage is not established by this report.</p>@else<p class="ua-meta">No matching browser pageviews are recorded for these filters.</p>@endif
    </section>
</div>
<section class="ua-card" id="pageview-pages">
    <div class="ua-section-heading"><div><h2>{{ $pageViews['selected_hub'] === 'all' ? 'Pages people visit' : 'Pages in '.\App\Activity\PageIdentity::HUBS[$pageViews['selected_hub']] }}</h2><p>Ranked by recorded pageviews</p></div>
        @if($pageViews['selected_hub'] !== 'all')<a href="{{ request()->fullUrlWithQuery(['page_hub' => 'all', 'pages_page' => null]) }}#pageview-pages">All hubs</a>@endif
    </div>
    <div class="ua-table-scroll"><table class="ua-table">
        <thead><tr><th scope="col">Page</th><th scope="col">Hub</th><th scope="col">Pageviews</th></tr></thead>
        <tbody>@forelse($pages as $page)
            <tr><td>{{ $page->name }}<small class="ua-meta">{{ $page->path ?? 'Public path unavailable' }}</small></td><td>{{ $page->hub_name }}</td><td>{{ number_format($page->total) }}</td></tr>
        @empty<tr><td colspan="3">No recorded pageviews match this hub and these filters.</td></tr>@endforelse</tbody>
    </table></div>
    @include('admin.components.pagination', ['paginator' => $pages, 'label' => 'Pages', 'unit' => 'pages'])
</section>
