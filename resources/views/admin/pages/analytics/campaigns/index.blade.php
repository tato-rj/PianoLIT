@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper"><div class="container-fluid ua">
    @include('admin.components.page.title', ['title' => 'Campaigns', 'subtitle' => 'Where campaign visitors come from, and which links lead to web memberships.'])

    <section class="ua-card" aria-label="Campaign tracking">
        <h2>{{ $enabled ? 'Campaign tracking is enabled' : 'Campaign tracking is disabled' }}</h2>
        <p>{{ $enabled ? 'New sources, mediums and campaigns appear automatically after a visitor allows measurement.' : 'Saved results are shown below. Enable CAMPAIGN_ATTRIBUTION_ENABLED in the server configuration to record new arrivals.' }}</p>
        <details><summary>How to tag a link</summary>
            <p class="mt-3">Add any of <code>utm_source</code>, <code>utm_medium</code> or <code>utm_campaign</code> to a public website or web app link. Each works on its own, or you can combine them. For example, <code>?utm_campaign=autumn_repertoire</code> tracks that campaign without a source or medium.</p>
            <p>To record all three, use <code>?utm_source=youtube.com&amp;utm_medium=video&amp;utm_campaign=autumn_repertoire</code>. Start with <code>&amp;</code> when the link already has a query string.</p>
            <p>Other sources, including <code>chatgpt.com</code>, <code>reddit.com</code> or your own campaign labels, work without setup. YouTube, ChatGPT and Reddit names with or without <code>.com</code> are grouped together.</p>
            <p>Optionally add <code>utm_content</code> to distinguish placements. Missing fields appear as not specified. Labels are limited to 100 characters; use letters, numbers, spaces, dots, hyphens or underscores. Sources cannot contain spaces. Avoid personal information.</p>
        </details>
    </section>

    @component('admin.components.table', [
        'id' => 'campaigns-table', 'label' => 'Campaigns', 'items' => $campaigns,
        'description' => 'Grouped by source, medium, campaign and placement.',
        'action' => route('admin.analytics.campaigns'), 'state' => $filters,
        'search' => ['label' => 'Find a source or campaign', 'placeholder' => 'YouTube, Reddit, campaign or placement'],
        'filters' => [['name' => 'period', 'label' => 'Arrival period', 'options' => \App\Services\CampaignAttributionReport::PERIODS]],
        'sorts' => \App\Services\CampaignAttributionReport::SORTS,
        'columns' => [
            ['label' => 'Source / medium', 'sort' => ['asc' => 'source_asc', 'desc' => 'source_desc']],
            'Campaign / placement', ['label' => 'Arrivals', 'sort' => ['asc' => 'arrivals_asc', 'desc' => 'arrivals_desc']],
            'Trials', 'First paid subscriptions', 'First payment revenue', 'Latest arrival',
        ],
        'emptyTitle' => 'No measured campaign arrivals',
        'emptyDescription' => 'Results appear after visitors open tagged links and allow measurement. Try another search or arrival period.',
    ])
        @foreach($campaigns as $campaign)
        <tr>
            <td><span class="admin-table__primary">{{ $campaign->utm_source !== '' ? $campaign->utm_source : 'No source specified' }}</span><small>{{ $campaign->utm_medium !== '' ? $campaign->utm_medium : 'No medium specified' }}</small></td>
            <td><span class="admin-table__primary">{{ $campaign->utm_campaign !== '' ? $campaign->utm_campaign : 'No campaign specified' }}</span><small>{{ $campaign->utm_content !== '' ? $campaign->utm_content : 'No placement specified' }}</small></td>
            <td>{{ number_format($campaign->arrivals) }}</td>
            <td>{{ number_format($campaign->trials) }}</td>
            <td>{{ number_format($campaign->first_paid_subscriptions) }}</td>
            <td>{{ $campaign->first_payment_revenue ?: '—' }}</td>
            <td>{{ carbon($campaign->last_arrived_at)->format('M j, Y') }}<small>{{ carbon($campaign->last_arrived_at)->format('g:i A') }}</small></td>
        </tr>
        @endforeach
    @endcomponent
    <p class="ua-note">Arrivals count consented first touches within a 30-day window, rather than every click or unique people. Trials and payments belong to the selected arrival period, even when they happen later. Revenue includes the first positive subscription payment, before fees and refunds; renewals are excluded and currencies stay separate. Declined measurement, blocked scripts, cross-device visits and excluded accounts are outside these results. Campaign cookies stay on the arrival host; moving between the website and web app does not carry attribution.</p>
</div></div>
@endsection
