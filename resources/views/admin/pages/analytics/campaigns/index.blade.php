@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper"><div class="container-fluid ua">
    @include('admin.components.page.title', ['title' => $conversionOnly ? 'Campaign conversions' : 'Campaigns', 'subtitle' => $conversionOnly ? 'Confirmed web memberships linked to a saved campaign' : 'Where campaign visitors come from, and which links lead to web memberships.'])

    @unless($conversionOnly)
    <section class="ua-card" aria-label="Campaign tracking">
        <h2>{{ $enabled ? 'Campaign tracking is enabled' : 'Campaign tracking is disabled' }}</h2>
        <p>{{ $enabled ? 'Tagged visits appear automatically. Account, trial and payment attribution is limited to visitors with an existing opt-in.' : 'Saved results are shown below. Enable CAMPAIGN_ATTRIBUTION_ENABLED in the server configuration to record new arrivals.' }}</p>
        <details><summary>How to tag a link</summary>
            <p class="mt-3">Add any of <code>utm_source</code>, <code>utm_medium</code> or <code>utm_campaign</code> to a public website or web app link. Each works on its own, or you can combine them. For example, <code>?utm_campaign=autumn_repertoire</code> tracks that campaign without a source or medium.</p>
            <p>To record all three, use <code>?utm_source=youtube.com&amp;utm_medium=video&amp;utm_campaign=autumn_repertoire</code>. Start with <code>&amp;</code> when the link already has a query string.</p>
            <p>Other sources, including <code>chatgpt.com</code>, <code>reddit.com</code> or your own campaign labels, work without setup. YouTube, ChatGPT and Reddit names with or without <code>.com</code> are grouped together.</p>
            <p>Optionally add <code>utm_content</code> to distinguish placements. Missing fields appear as not specified. Labels are limited to 100 characters; use letters, numbers, spaces, dots, hyphens or underscores. Sources cannot contain spaces. Avoid personal information.</p>
        </details>
    </section>

    @endunless

    @component('admin.components.table', [
        'id' => 'campaigns-table', 'label' => 'Campaigns', 'items' => $campaigns,
        'description' => 'Grouped by source, medium, campaign, placement and landing page.',
        'action' => route('admin.analytics.campaigns'), 'state' => $filters,
        'search' => ['label' => 'Find a source, campaign or page', 'placeholder' => 'YouTube, campaign or /pieces/174'],
        'filters' => [['name' => 'period', 'label' => $conversionOnly ? 'Campaign captured' : 'Arrival period', 'options' => $conversionOnly ? ['7' => 'Last 7 days', '30' => 'Last 30 days', '90' => 'Last 90 days', 'all' => 'All retained links'] : \App\Services\CampaignAttributionReport::PERIODS]],
        'sorts' => $sorts,
        'columns' => $conversionOnly ? ['Source / medium', 'Campaign / placement', 'Landing page', 'Trials', 'First paid subscriptions', 'First payment revenue'] : [
            ['label' => 'Source / medium', 'sort' => ['asc' => 'source_asc', 'desc' => 'source_desc']],
            'Campaign / placement', 'Landing page', ['label' => 'Arrivals', 'sort' => ['asc' => 'arrivals_asc', 'desc' => 'arrivals_desc']],
            'Trials', 'First paid subscriptions', 'First payment revenue', 'Latest arrival',
        ],
        'emptyTitle' => $conversionOnly ? 'No linked campaign conversions' : 'No measured campaign arrivals',
        'emptyDescription' => $conversionOnly ? 'Confirmed memberships appear when a saved campaign has attribution permission. General visits are reported in Google Analytics.' : 'Results appear when visitors open valid tagged links. Try another search or arrival period.',
    ])
        @foreach($campaigns as $campaign)
        <tr>
            <td><span class="admin-table__primary">{{ $campaign->utm_source !== '' ? $campaign->utm_source : 'No source specified' }}</span><small>{{ $campaign->utm_medium !== '' ? $campaign->utm_medium : 'No medium specified' }}</small></td>
            <td><span class="admin-table__primary">{{ $campaign->utm_campaign !== '' ? $campaign->utm_campaign : 'No campaign specified' }}</span><small>{{ $campaign->utm_content !== '' ? $campaign->utm_content : 'No placement specified' }}</small></td>
            <td>
                @if($campaign->landing_page_url)
                    <a class="admin-table__primary" href="{{ $campaign->landing_page_url }}" target="_blank" rel="noopener noreferrer">{{ $campaign->landing_page_label }}</a>
                @else
                    <span class="admin-table__primary">{{ $campaign->landing_page_label }}</span>
                @endif
                @if($campaign->landing_page_location)<small>{{ $campaign->landing_page_location }}</small>@endif
            </td>
            @unless($conversionOnly)<td>{{ number_format($campaign->arrivals) }}</td>@endunless
            <td>{{ number_format($campaign->trials) }}</td>
            <td>{{ number_format($campaign->first_paid_subscriptions) }}</td>
            <td>{{ $campaign->first_payment_revenue ?: '—' }}</td>
            @unless($conversionOnly)<td>{{ carbon($campaign->last_arrived_at)->format('M j, Y') }}<small>{{ carbon($campaign->last_arrived_at)->format('g:i A') }}</small></td>@endunless
        </tr>
        @endforeach
    @endcomponent
    @if($conversionOnly)
    <p class="ua-note">Only saved campaign links with attribution permission appear here. The period refers to when the campaign was captured; conversions may happen later. Revenue is the first positive payment, before fees and refunds; renewals stay out and currencies remain separate. Missing permission, blocked analytics and host-local attribution can leave a membership unlinked. Google Analytics reports general traffic.</p>
    @else
    <p class="ua-note">Arrivals count the first tagged visit per browser session, or per 30-day campaign cookie after opt-in; they are not every click or unique people. Anonymous visits need no button click or JavaScript. Account, trial and payment attribution requires an existing opt-in. Trials and payments belong to the selected arrival period, even when they happen later. Revenue includes the first positive subscription payment, before fees and refunds; renewals are excluded and currencies stay separate. Declined measurement, known bots and excluded accounts stay out. Attribution stays on the arrival host and does not follow visitors across devices.</p>
    @endif
</div></div>
@endsection
