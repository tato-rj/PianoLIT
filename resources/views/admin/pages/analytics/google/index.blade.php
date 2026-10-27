@extends('admin.layouts.app')

@php
    $reports = [
        'overview' => 'https://analytics.google.com/analytics/web/#/a137232556p399721623/reports/reportinghub',
        'hubs' => 'https://analytics.google.com/analytics/web/#/a137232556p399721623/reports/explorer?params=_u..nav%3Dmaui&collectionId=16215843872&r=16215740339',
        'pages' => 'https://analytics.google.com/analytics/web/#/a137232556p399721623/reports/explorer?params=_u..nav%3Dmaui&collectionId=16215843872&r=all-pages-and-screens',
        'landing' => 'https://analytics.google.com/analytics/web/#/a137232556p399721623/reports/explorer?params=_u..nav%3Dmaui&collectionId=16215843872&r=landing-page',
        'traffic' => 'https://analytics.google.com/analytics/web/#/a137232556p399721623/reports/explorer?params=_u..nav%3Dmaui%26_r.explorerCard..seldim%3D%5B%22sessionSourceMedium%22%5D&collectionId=16215843872&r=lifecycle-traffic-acquisition-v2',
        'retention' => 'https://analytics.google.com/analytics/web/#/a137232556p399721623/reports/dashboard?params=_u..nav%3Dmaui&collectionId=10727765276&r=lifecycle-retention-overview',
        'countries' => 'https://analytics.google.com/analytics/web/#/a137232556p399721623/reports/explorer?params=_u..nav%3Dmaui&collectionId=user&r=user-demographics-detail',
        'devices' => 'https://analytics.google.com/analytics/web/#/a137232556p399721623/reports/explorer?params=_u..nav%3Dmaui%26_r.explorerCard..sortKey%3Dd-0%26_r.explorerCard..seldim%3D%5B%22deviceCategory%22%5D&collectionId=user&r=user-technology-detail',
        'trials' => 'https://analytics.google.com/analytics/web/#/a137232556p399721623/reports/explorer?params=_u..nav%3Dmaui%26_r.explorerCard..filterTerm%3Dmembership_trial_started%26_r.explorerCard..startRow%3D0&collectionId=10727765276&r=top-events',
        'payments' => 'https://analytics.google.com/analytics/web/#/a137232556p399721623/reports/explorer?params=_u..nav%3Dmaui%26_r.explorerCard..filterTerm%3Dpurchase%26_r.explorerCard..startRow%3D0&collectionId=10727765276&r=top-events',
        'campaigns' => 'https://analytics.google.com/analytics/web/#/a137232556p399721623/reports/explorer?params=_u..nav%3Dmaui%26_r.explorerCard..seldim%3D%5B%22sessionCampaignName%22%5D&collectionId=16215843872&r=lifecycle-traffic-acquisition-v2',
    ];
    $columns = [
        [
            ['id' => 'traffic', 'title' => 'Traffic', 'metrics' => [
                ['label' => 'Visitors & sessions', 'description' => 'Visitors (active users) and visits (sessions). Track overall growth.', 'report' => 'overview'],
                ['label' => 'Page views', 'description' => 'Page loads, including repeats. Find your most-viewed content.', 'report' => 'pages'],
                ['label' => 'Traffic by hub', 'description' => 'Traffic per hub. See which PianoLIT worlds attract visitors.', 'report' => 'hubs'],
                ['label' => 'Traffic sources', 'description' => 'Where visits come from: Google, email, social and more. Find your strongest channels.', 'report' => 'traffic'],
                ['label' => 'Landing pages', 'description' => 'Where visits begin. Find pages that keep people interested.', 'report' => 'landing'],
                ['label' => 'Engagement rate', 'description' => 'Share of visits over 10 seconds, converting or viewing 2+ pages. Gauge interest.', 'report' => 'traffic'],
            ]],
        ],
        [
            ['id' => 'audience', 'title' => 'Audience', 'metrics' => [
                ['label' => 'Retention', 'description' => 'Visitors returning after their first visit. Measure lasting interest.', 'report' => 'retention'],
                ['label' => 'Countries', 'description' => 'Where visitors are located. Spot growing audiences.', 'report' => 'countries'],
                ['label' => 'Devices', 'description' => 'Mobile, desktop and tablet traffic. Prioritize the right screens.', 'report' => 'devices'],
            ]],
            ['id' => 'campaigns', 'title' => 'Campaigns & memberships', 'metrics' => [
                ['label' => 'Campaign traffic', 'description' => 'Visits by tagged campaign, including Free Pick emails. Compare reach.', 'report' => 'campaigns'],
                ['label' => 'Trial starts', 'description' => 'Confirmed web trials. See which campaigns bring potential members.', 'report' => 'trials'],
                ['label' => 'First payments & revenue', 'description' => 'First membership payments and revenue. Compare paying customers; renewals excluded.', 'report' => 'payments'],
            ], 'note' => 'For conversion results, add “Membership campaign” in GA. Opted-in web memberships only.'],
        ],
    ];
@endphp

@section('content')
<div class="content-wrapper"><div class="container-fluid ua">
    @include('admin.components.page.title', ['title' => 'Google Analytics', 'subtitle' => 'The key numbers for every PianoLIT hub'])

    <nav class="d-flex flex-wrap gap-2 mb-4" aria-label="Analytics topics">
        <a class="btn btn-secondary btn-sm" href="#traffic">Traffic</a>
        <a class="btn btn-secondary btn-sm" href="#audience">Audience</a>
        <a class="btn btn-secondary btn-sm" href="#campaigns">Campaigns</a>
    </nav>

    <div class="row g-4">
        @foreach($columns as $sections)
        <div class="col-xl-6">
            @foreach($sections as $section)
            <section class="ua-card" id="{{ $section['id'] }}" aria-labelledby="{{ $section['id'] }}-heading">
                <h2 id="{{ $section['id'] }}-heading">{{ $section['title'] }}</h2>
                @foreach($section['metrics'] as $metric)
                <div class="row align-items-center g-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                    <div class="col">
                        <h3 class="mb-1">{{ $metric['label'] }}</h3>
                        <p class="mb-0 text-muted">{{ $metric['description'] }}</p>
                    </div>
                    <div class="col-auto">
                        <a class="btn btn-dark btn-sm d-inline-flex align-items-center gap-2" href="{{ $reports[$metric['report']] }}" target="_blank" rel="noopener noreferrer" aria-label="Open {{ $metric['label'] }} in Google Analytics (opens in a new tab)">
                            View @icon('arrow-up-right', ['mr' => 0])
                        </a>
                    </div>
                </div>
                @endforeach
                @isset($section['note'])<p class="ua-note mt-3 mb-0">{{ $section['note'] }}</p>@endisset
            </section>
            @endforeach
        </div>
        @endforeach
    </div>
</div></div>
@endsection
