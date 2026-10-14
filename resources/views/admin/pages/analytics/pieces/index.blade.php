@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" href="{{ asset('css/vendor/flag-icon/flag-icon.min.css') }}">
@endsection

@section('content')
<div class="content-wrapper"><div class="container-fluid piece-analytics">

@include('admin.components.page.title', ['title' => 'Repertoire analytics', 'subtitle' => 'Catalogue composition, media coverage and the pieces people open and save.'])
    <div class="pa-toolbar"><p>Current catalogue · all pieces</p><a class="pa-button" href="{{ route('admin.pieces.index') }}">Browse pieces
@icon('arrow-up-right', ['mr' => 0])</a></div>
    <div class="pa-metrics">
        <article class="pa-metric pa-metric--lavender"><span>
@icon('music', ['mr' => 0]) Pieces in the catalogue</span><strong>{{ number_format($report['summary']['total']) }}</strong><p>Each piece is counted once.</p></article>
        <article class="pa-metric pa-metric--mint"><span>
@icon('users', ['mr' => 0]) Composers represented</span><strong>{{ number_format($report['summary']['composers']) }}</strong><p>Composers with at least one piece.</p></article>
        <article class="pa-metric pa-metric--peach"><span>
@icon('chart-line', ['mr' => 0]) Added in the last {{ $report['months'] }} months</span><strong>{{ number_format(array_sum(array_column($report['growth'], 'added'))) }}</strong><p>Calendar months · current month to date.</p><div class="pa-spark" aria-hidden="true">
@foreach($report['growth'] as $month)<i style="height:{{ $month['added'] ? max(4, $month['added'] / max(1, max(array_column($report['growth'], 'added'))) * 100) : 0 }}%"></i>
@endforeach</div></article>
        <article class="pa-metric pa-metric--gold"><span>
@icon('video', ['mr' => 0]) Missing media</span><strong>{{ number_format($report['summary']['total'] - $report['summary']['complete']) }}</strong><p>Missing a score, full audio or video.</p></article>
    </div>

    <section class="pa-section" aria-labelledby="pa-coverage-title">
        <div class="pa-section-heading"><h2 id="pa-coverage-title">Catalogue growth & media</h2><p>How the library has grown, and what is available now.</p></div>
        <div class="pa-grid pa-grid--wide">

@include('admin.pages.analytics.pieces.growth', ['trend' => $report['growth']])
            <article class="pa-card"><div class="pa-card-heading"><div><h3>Media coverage</h3><p>Share of all {{ number_format($report['summary']['total']) }} pieces.</p></div></div>
                <div class="pa-media-grid">
@foreach($report['media'] as $media)<div class="pa-media" style="--pa-color:{{ $media['color'] }}">
                    <svg class="pa-gauge" viewBox="0 0 120 72" role="img" aria-label="{{ $media['label'] }}: {{ $media['total'] }} of {{ $report['summary']['total'] }} pieces, {{ $media['share'] }} percent"><path class="pa-gauge-track" d="M 12 60 A 48 48 0 0 1 108 60" pathLength="100"/><path class="pa-gauge-fill" d="M 12 60 A 48 48 0 0 1 108 60" pathLength="100" opacity="{{ $media['total'] ? 1 : 0 }}" stroke-dasharray="{{ $media['share'] }} 100"/><text x="60" y="56" text-anchor="middle">{{ $media['share'] }}%</text></svg>
                    <h4>{{ $media['label'] }}</h4><p><strong>{{ number_format($media['total']) }}</strong> / {{ number_format($report['summary']['total']) }}</p><small>{{ $media['description'] }}</small>
                </div>
@endforeach</div>
                <p class="pa-note">Based on saved paths and links; file and remote-link health is not checked. Separate-hand recordings are not full audio.</p>
            </article>
        </div>
    </section>

    <section class="pa-section" aria-labelledby="pa-composition-title">
        <div class="pa-section-heading"><h2 id="pa-composition-title">Levels & periods</h2><p>See the balance of the catalogue and where media coverage differs.</p></div>
        <div class="pa-grid pa-grid--wide">

@include('admin.pages.analytics.pieces.levels', ['levels' => $report['levels']])
            <article class="pa-card"><div class="pa-card-heading"><div><h3>Pieces by period</h3><p>Saved piece tags · share of the entire catalogue.</p></div></div>

@include('admin.pages.analytics.pieces.donut', ['categories' => $report['periods'], 'total' => $report['summary']['total'], 'label' => 'Pieces by period'])
                <p class="pa-note">A piece with more than one period is shown under Multiple periods. Missing tags stay visible.</p>
            </article>
        </div>

@include('admin.pages.analytics.pieces.matrix', ['matrix' => $report['matrix']])
    </section>


@include('admin.pages.analytics.pieces.countries', ['map' => $report['countries']])

    <section class="pa-section" aria-labelledby="pa-composers-title">
        <div class="pa-section-heading"><h2 id="pa-composers-title">Composers & piece length</h2><p>Which composers fill the library, and how the pieces are classified.</p></div>
        <div class="pa-grid">
            <article class="pa-card"><div class="pa-card-heading"><div><h3>Most represented composers</h3><p>Top eight · counts and shares of all pieces.</p></div></div>

@include('admin.pages.analytics.pieces.bars', ['rows' => $report['composers'], 'links' => 'admin.composers.edit', 'color' => '#a692ce', 'empty' => 'No composer records are linked to pieces.'])
                <p class="pa-note">These {{ count($report['composers']) }} composers account for {{ $report['summary']['total'] ? round(array_sum(array_column($report['composers'], 'total')) / $report['summary']['total'] * 100, 1) : 0 }}% of the catalogue.</p>
            </article>
            <article class="pa-card"><div class="pa-card-heading"><div><h3>Pieces by length</h3><p>Saved length tags · counts and catalogue shares.</p></div></div>

@include('admin.pages.analytics.pieces.bars', ['rows' => $report['lengths'], 'color' => '#83c9df'])
                <div class="pa-subsection"><h3>Composer gender</h3><p class="pa-note">Counts pieces, weighted by the saved composer record.</p>

@include('admin.pages.analytics.pieces.donut', ['categories' => $report['genders'], 'total' => $report['summary']['total'], 'label' => 'Pieces by composer gender', 'compact' => true])
                </div>
            </article>
        </div>
    </section>

    <section class="pa-section" aria-labelledby="pa-engagement-title">
        <div class="pa-section-heading"><h2 id="pa-engagement-title">Pieces people open & save</h2><p>All-time recorded piece views and current saved favorites · top eight each.</p></div>
        <div class="pa-grid">

@include('admin.pages.analytics.pieces.ranking', ['title' => 'Most viewed pieces', 'rows' => $report['views'], 'unit' => 'views', 'color' => '#efb07c'])

@include('admin.pages.analytics.pieces.ranking', ['title' => 'Most favorited pieces', 'rows' => $report['favorites'], 'unit' => 'favorites', 'color' => '#94c6b0'])
        </div>
        <p class="pa-note">Views are recorded piece-view rows, not unique visitors or all activity events. Favorites are saved records, not lifetime save actions. Neither ranking measures performance during a free-pick feature. Excluded accounts are omitted.</p>
    </section>

    <section class="pa-section pa-review" aria-labelledby="pa-review-title">
        <div class="pa-section-heading"><h2 id="pa-review-title">Classification to review</h2><p>Counts can overlap when a piece needs more than one correction.</p></div>
        <div class="pa-issues">
@foreach($report['issues'] as $issue)<div><strong>{{ number_format($issue['total']) }}</strong><span>{{ $issue['label'] }}</span></div>
@endforeach</div>
        <p class="pa-note">Level and period charts count each piece once. Duplicate tags with the same name count as one classification; conflicting names appear as Multiple levels or Multiple periods. Growth describes pieces still in the catalogue, so removed pieces are not reconstructed.</p>
    </section>
</div></div>
@endsection

@section('scripts')
<script src="{{ mix('js/views/admin-user-map.js') }}" defer></script>
@endsection
