@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" href="{{ asset('css/vendor/flag-icon/flag-icon.min.css') }}">
@endsection

@section('content')
<div class="content-wrapper"><div class="container-fluid composer-analytics">
    @include('admin.components.page.title', ['title' => 'Composer analytics', 'subtitle' => 'Repertoire coverage, audience interest and opportunities to grow the library.'])
    <div class="ca-context"><span>Current composer catalogue · all-time recorded interest</span><a href="#composer-directory">Explore composers @icon('arrow-down', ['mr' => 0])</a></div>

    <div class="ca-metrics">
        <article class="ca-metric ca-metric--lavender"><span>@icon('users', ['mr' => 0]) Composers</span><strong>{{ number_format($summary['total']) }}</strong><p>{{ number_format($summary['represented']) }} with pieces · {{ number_format($summary['empty']) }} without pieces</p></article>
        <article class="ca-metric ca-metric--mint"><span>@icon('music', ['mr' => 0]) Linked pieces</span><strong>{{ number_format($summary['pieces']) }}</strong><p>{{ $summary['top_share'] }}% belong to the five largest composer catalogues.</p></article>
        <article class="ca-metric ca-metric--peach"><span>@icon('layers', ['mr' => 0]) Small catalogues</span><strong>{{ number_format($summary['small']) }}</strong><p>Composers with 1–3 pieces. <a href="{{ route('admin.analytics.composers', ['depth' => 'small']) }}#composer-directory">Review composers @icon('arrow-up-right', ['mr' => 0])</a></p></article>
        <article class="ca-metric ca-metric--gold"><span>@icon('globe', ['mr' => 0]) Countries represented</span><strong>{{ number_format(count($countries['countries'])) }}</strong><p>{{ number_format($countries['unlocated']) }} {{ $countries['unlocated'] === 1 ? 'composer has' : 'composers have' }} no usable country recorded.</p></article>
    </div>

    <div class="ca-grid ca-grid--wide ca-section">
        @include('admin.pages.analytics.composers.growth')
        <article class="ca-card"><div class="ca-heading"><div><h2>Repertoire & profile coverage</h2><p>Share of all {{ number_format($summary['total']) }} composer records. Click a gap to review it.</p></div></div>
            <div class="ca-gauges">
                @foreach(['represented' => ['With pieces', 'depth', 'empty', '#94c6b0'], 'biography' => ['Biography saved', 'profile', 'biography', '#a692ce'], 'portrait' => ['Portrait saved', 'profile', 'portrait', '#efb07c']] as $key => $definition)
                @php($share = $summary['total'] ? round($summary[$key] / $summary['total'] * 100, 1) : 0)
                <a href="{{ route('admin.analytics.composers', [$definition[1] => $definition[2]]) }}#composer-directory" class="ca-gauge" style="--ca-color:{{ $definition[3] }}">
                    <svg viewBox="0 0 120 72" role="img" aria-label="{{ $definition[0] }}: {{ $summary[$key] }} of {{ $summary['total'] }} composers"><path class="ca-gauge-track" d="M 12 60 A 48 48 0 0 1 108 60" pathLength="100"/><path class="ca-gauge-fill" d="M 12 60 A 48 48 0 0 1 108 60" pathLength="100" opacity="{{ $summary[$key] ? 1 : 0 }}" stroke-dasharray="{{ $share }} 100"/><text x="60" y="56" text-anchor="middle">{{ $share }}%</text></svg>
                    <strong>{{ $definition[0] }}</strong><span>{{ number_format($summary[$key]) }} / {{ number_format($summary['total']) }}</span><small>{{ number_format($summary['total'] - $summary[$key]) }} to review @icon('arrow-up-right', ['mr' => 0])</small>
                </a>
                @endforeach
            </div>
            <p class="ca-note">Biography and portrait coverage checks saved text and paths. It does not check editorial quality or whether an image loads.</p>
        </article>
    </div>

    <section class="ca-section" aria-labelledby="ca-interest-title">
        <div class="ca-section-heading"><h2 id="ca-interest-title">Composer interest</h2><p>Compare recorded views and current saves alongside the size of each catalogue.</p></div>
        <div class="ca-grid">
            @include('admin.pages.analytics.composers.ranking', ['title' => 'Most viewed composers', 'rows' => $rankings['views'], 'metric' => 'views', 'unit' => 'views', 'color' => '#efb07c', 'empty' => 'No piece views have been recorded.'])
            @include('admin.pages.analytics.composers.ranking', ['title' => 'Most saved composers', 'rows' => $rankings['saves'], 'metric' => 'saves', 'unit' => 'saves', 'color' => '#94c6b0', 'empty' => 'No current saves have been recorded.'])
        </div>
        <p class="ca-note ca-scope">Views count retained piece-view records, not unique visitors, media plays or all activity events. Saves count each account–piece pair once across folders; removed saves are absent. Analytics opt-outs are excluded. These are different measures, so they are shown separately.</p>
    </section>

    <section class="ca-section" aria-labelledby="ca-balance-title">
        <div class="ca-section-heading"><h2 id="ca-balance-title">Catalogue balance</h2><p>Composer records count equally here, whether they have one piece or a hundred.</p></div>
        <div class="ca-grid ca-grid--wide">
            @include('admin.pages.analytics.composers.periods')
            <article class="ca-card"><div class="ca-heading"><div><h3>Pieces per composer</h3><p>Each composer belongs to one catalogue-size group.</p></div></div>
                @include('admin.pages.analytics.composers.donut', ['categories' => $depths, 'label' => 'Pieces per composer', 'links' => true])
                <div class="ca-subsection"><h3>Composer gender</h3><p class="ca-note">Saved profile values · missing values remain visible.</p>
                    @include('admin.pages.analytics.composers.donut', ['categories' => $genders, 'label' => 'Composer gender', 'links' => false, 'compact' => true])
                </div>
            </article>
        </div>
    </section>

    @include('admin.pages.analytics.composers.countries', ['map' => $countries])

    <section class="ca-section" aria-labelledby="ca-planning-title">
        <div class="ca-section-heading"><h2 id="ca-planning-title">Plan repertoire & features</h2><p>Use current saves and upcoming dates to choose what to review next.</p></div>
        <div class="ca-grid">
            <article class="ca-card"><div class="ca-heading"><div><h3>Small catalogues with saves</h3><p>Up to five composers with 1–3 pieces, ranked by saves per piece.</p></div></div>
                <ol class="ca-opportunities">
                    @forelse($opportunities as $composer)
                    <li><span class="ca-rank">{{ $loop->iteration }}</span><div><a href="{{ route('admin.composers.edit', $composer['id']) }}">{{ $composer['name'] }}</a><small>{{ $composer['pieces'] }} {{ $composer['pieces'] === 1 ? 'piece' : 'pieces' }} · {{ number_format($composer['saves']) }} saves · {{ number_format($composer['savers']) }} {{ $composer['savers'] === 1 ? 'account' : 'accounts' }}</small></div><strong>{{ $composer['saves_per_piece'] }}<small>saves / piece</small></strong></li>
                    @empty<li class="ca-empty">No saves are recorded for composers with 1–3 pieces yet.</li>@endforelse
                </ol>
                <p class="ca-note">A starting point for repertoire review. Small samples can rank highly; this does not measure unmet demand or a save conversion rate.</p>
            </article>
            <article class="ca-card"><div class="ca-heading"><div><h3>Upcoming composer dates</h3><p>{{ count($anniversaries) }} birthdays and death anniversaries · {{ now()->format('M j') }}–{{ now()->addDays(29)->format('M j, Y') }}.</p></div></div>
                <ol class="ca-calendar">
                    @forelse(array_slice($anniversaries, 0, 6) as $event)
                    <li><time datetime="{{ $event['date']->format('Y-m-d') }}"><strong>{{ $event['date']->format('d') }}</strong><span>{{ $event['date']->format('M') }}</span></time><div><a href="{{ route('admin.composers.edit', $event['id']) }}">{{ $event['name'] }}</a><small>{{ $event['type'] }} · {{ $event['years'] }} years</small><small>{{ $event['pieces'] }} {{ $event['pieces'] === 1 ? 'piece available' : 'pieces available' }}</small></div></li>
                    @empty<li class="ca-empty">No exact composer dates fall within the next 30 days.</li>@endforelse
                </ol>
                @if(count($anniversaries) > 6)<details class="ca-details"><summary>All {{ count($anniversaries) }} upcoming dates</summary><ul class="ca-date-list">@foreach($anniversaries as $event)<li><time>{{ $event['date']->format('M j') }}</time><a href="{{ route('admin.composers.edit', $event['id']) }}">{{ $event['name'] }}</a><span>{{ $event['type'] }} · {{ $event['years'] }} years</span></li>@endforeach</ul></details>@endif
                <p class="ca-note">Includes today. Approximate, invalid and future source dates are omitted. February 29 anniversaries appear only in leap years.</p>
            </article>
        </div>
    </section>

    @include('admin.pages.analytics.composers.directory')
</div></div>
@endsection

@section('scripts')
<script src="{{ mix('js/views/admin-user-map.js') }}" defer></script>
@endsection
