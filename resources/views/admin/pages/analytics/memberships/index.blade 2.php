@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper"><div class="container-fluid ua ma">
    @include('admin.components.page.title', ['title' => 'Membership analytics', 'subtitle' => 'Stripe and Apple memberships, automatic Apple checks and super-user access.'])
    @php
        $reviewCount = $totals['needs_review'] + $totals['pending'] + $totals['payment_issue'];
        $inactiveCount = $totals['expired'] + $totals['revoked'] + $totals['paused'];
        $enabled = config('apple-membership-monitor.enabled');
        $overdue = $lastRun && carbon($lastRun->started_at)->lt(now()->subHours(2));
        $running = $lastRun && !$lastRun->finished_at && !$overdue;
    @endphp
    <div class="ma-metrics">
        @foreach([
            ['Active', $totals['active'] + $totals['grace_period'], 'Paid memberships, including billing grace periods.', 'active'],
            ['Trials', $totals['trial'], 'Current trials confirmed by the provider.', 'trial'],
            ['Ending', $totals['ending'], 'Still valid, with automatic renewal turned off.', 'ending'],
            ['Expired or inactive', $inactiveCount, 'Expired, paused, refunded or revoked.', 'expired'],
            ['Needs review', $reviewCount, 'Unverified Apple receipts or Stripe payment issues.', 'needs_review'],
        ] as $metric)
        <div class="ua-card ma-metric ma-metric--{{$metric[3]}}"><span>{{$metric[0]}}</span><strong>{{number_format($metric[1])}}</strong><small>{{$metric[2]}}</small></div>
        @endforeach
    </div>

    <div class="ma-summary-grid">
        <section class="ua-card ma-providers" aria-labelledby="ma-providers-title">
            <div class="ua-section-heading"><div><h2 id="ma-providers-title">Memberships by provider</h2><p>Super-user access is separate from Stripe and Apple billing totals.</p></div></div>
            <p class="ma-scroll-hint">Scroll sideways to see all columns.</p>
            <div class="ua-table-scroll" tabindex="0" role="region" aria-label="Provider comparison"><table class="ua-table ma-provider-table">
                <thead><tr><th scope="col">Provider</th><th scope="col">Active</th><th scope="col">Trial</th><th scope="col">Ending</th><th scope="col">Inactive</th><th scope="col">Review</th></tr></thead>
                <tbody>@foreach(['stripe' => 'Stripe · Webapp', 'apple' => 'Apple · iOS', 'super_user' => 'Super User'] as $key => $label)
                    <tr><th scope="row"><a href="{{route('admin.analytics.memberships', ['provider' => $key])}}">{{$label}}</a></th><td>{{$counts[$key]['active'] + $counts[$key]['grace_period']}}</td><td>{{$counts[$key]['trial']}}</td><td>{{$counts[$key]['ending']}}</td><td>{{$counts[$key]['expired'] + $counts[$key]['revoked'] + $counts[$key]['paused']}}</td><td>{{$counts[$key]['needs_review'] + $counts[$key]['pending'] + $counts[$key]['payment_issue']}}</td></tr>
                @endforeach</tbody>
            </table></div>
            <p class="ma-source-note">Stripe uses saved billing updates. Apple uses the latest automatic receipt check. Super users have full access regardless of billing status and may also appear in billing totals. Sandbox purchases and excluded test accounts are outside billing totals.</p>
        </section>

        <section class="ua-card ma-monitor" aria-labelledby="ma-monitor-title">
            <div class="ua-section-heading"><div><h2 id="ma-monitor-title">Apple status checks</h2><p>Receipts are checked individually. A failed receipt does not stop the others.</p></div><span class="ma-status ma-status--{{!$monitorReady || !$enabled || $overdue ? 'needs_review' : 'active'}}">{{!$monitorReady ? 'Setup required' : (!$enabled ? 'Disabled' : ($overdue ? 'Check overdue' : ($running ? 'Checking' : 'Automatic')))}}</span></div>
            @if(!$monitorReady)
                <p>Automatic checks need server setup before Apple status can be confirmed.</p>
            @elseif(!$enabled)
                <p>Automatic checking is disabled on the server. Saved results are shown below.</p>
            @elseif(!$lastRun)
                <p>Waiting for the first scheduled check. Existing Apple memberships remain unverified here until Apple responds.</p>
            @else
                <p class="ma-run-time">Last run <strong>{{carbon($lastRun->started_at)->format('M j, g:i A')}}</strong></p>
                <div class="ma-run-counts"><span><strong>{{$lastRun->checked}}</strong> checked</span><span><strong>{{$lastRun->verified}}</strong> verified</span><span><strong>{{$lastRun->failed}}</strong> failed</span></div>
                @if($overdue)<p class="ma-source-note">The scheduled check is overdue. Check the server scheduler.</p>@endif
            @endif
            <p class="ma-source-note">The scheduler checks every hour. Valid receipts refresh at least daily; active memberships refresh every six hours and at expiration. Failed checks retry automatically.</p>
            <a class="ma-review-link" href="{{route('admin.analytics.memberships', ['provider' => 'apple', 'status' => 'needs_review'])}}">View Apple memberships needing review @icon('arrow-right', ['mr' => 0])</a>
        </section>
    </div>

    <section class="ua-card ma-directory" aria-labelledby="ma-directory-title">
        <div class="ua-section-heading"><div><h2 id="ma-directory-title">Membership directory</h2><p>{{number_format($memberships->total())}} matching accounts · One row per account.</p></div></div>
        <form class="ua-filters" method="GET" action="{{route('admin.analytics.memberships')}}" aria-label="Membership filters">
            <label class="ua-search">Find an account<input type="search" name="search" value="{{$filters['search'] ?? ''}}" placeholder="Name, email or user ID" maxlength="160"></label>
            <label>Provider<select name="provider"><option value="">All providers</option>@foreach(['billing' => 'Stripe + Apple', 'stripe' => 'Stripe · Webapp', 'apple' => 'Apple · iOS', 'super_user' => 'Super User'] as $key => $label)<option value="{{$key}}" @if(($filters['provider'] ?? '') === $key) selected @endif>{{$label}}</option>@endforeach</select></label>
            <label>Status<select name="status"><option value="">All statuses</option>@foreach($labels as $key => $label)<option value="{{$key}}" @if(($filters['status'] ?? '') === $key) selected @endif>{{$label}}</option>@endforeach</select></label>
            <label>Sort by<select name="sort">@foreach(['date_asc' => 'Access date · earliest', 'date_desc' => 'Access date · latest', 'name' => 'Account name', 'checked' => 'Latest check / grant'] as $key => $label)<option value="{{$key}}" @if(($filters['sort'] ?? 'date_asc') === $key) selected @endif>{{$label}}</option>@endforeach</select></label>
            <button class="ua-button" type="submit">Apply filters</button>
            @if(array_filter($filters))<a class="ma-reset" href="{{route('admin.analytics.memberships')}}">Reset</a>@endif
        </form>
        <p class="ma-scroll-hint">Scroll sideways to see status, dates and checks.</p>
        <div class="ua-table-scroll" tabindex="0" role="region" aria-label="Membership results"><table class="ua-table ma-table">
            <thead><tr><th scope="col">Account</th><th scope="col">Provider / plan</th><th scope="col">Status</th><th scope="col">Access until</th><th scope="col">Latest check</th></tr></thead>
            <tbody>@forelse($memberships as $membership)
                <tr>
                    <td><a class="ua-person-name" href="{{route('admin.users.show', $membership->user_id)}}">{{trim($membership->first_name.' '.$membership->last_name)}}</a><small>{{$membership->email}}</small><small>#{{$membership->user_id}}</small></td>
                    <td><span class="ma-provider">@icon($membership->provider === 'super_user' ? 'user-shield' : ($membership->provider === 'apple' ? 'brand-apple' : 'credit-card'), ['mr' => 1]){{['super_user' => 'Super User', 'apple' => 'Apple', 'stripe' => 'Stripe'][$membership->provider]}}</span>@if($membership->provider !== 'super_user')<small>{{\App\Analytics\MembershipReport::planName($membership->plan)}}</small>@endif @if($membership->environment === 'Sandbox')<small>Test environment</small>@endif</td>
                    <td><span class="ma-status ma-status--{{$membership->membership_status}}">{{$labels[$membership->membership_status]}}</span>@if($membership->membership_status === 'ending')<small>Will not renew</small>@elseif($membership->membership_status === 'pending')<small>Waiting for a current receipt check</small>@endif</td>
                    <td class="ma-date">@if($membership->provider !== 'super_user'){{$membership->access_until ? carbon($membership->access_until)->format('M j, Y') : ($membership->membership_status === 'revoked' ? 'Access revoked' : 'Not confirmed')}}@if($membership->access_until)<small>{{carbon($membership->access_until)->format('g:i A')}}</small>@if(in_array($membership->membership_status, ['needs_review', 'pending']))<small>Last verified date</small>@endif @endif @endif</td>
                    <td class="ma-check">
                        @if($membership->provider === 'super_user')
                            @if($membership->checked_at)<span>{{carbon($membership->checked_at)->format('M j, Y')}}</span><small>{{carbon($membership->checked_at)->format('g:i A')}}</small>@endif
                        @elseif($membership->provider === 'stripe')<span>Saved Stripe update</span><small>{{carbon($membership->source_updated_at)->format('M j, g:i A')}}</small>
                        @elseif($membership->checked_at)<span>{{carbon($membership->checked_at)->format('M j, g:i A')}}</span>
                            @if($membership->error_code)<details class="ma-error"><summary>Check failed @if(ctype_digit($membership->error_code)) · {{$membership->error_code}}@endif</summary><p>{{\App\Analytics\MembershipReport::errorText($membership->error_code)}}</p>@if($membership->verified_at)<p>Last successful check: {{carbon($membership->verified_at)->format('M j, Y, g:i A')}}.</p>@endif</details>@endif
                            @if($membership->next_check_at)<small>Next check {{carbon($membership->next_check_at)->diffForHumans()}}</small>@endif
                        @else<span>No automatic check yet</span>@endif
                    </td>
                </tr>
            @empty<tr><td colspan="5"><div class="ua-empty"><h3>No matching memberships</h3><p>Try another account, provider or status.</p></div></td></tr>@endforelse</tbody>
        </table></div>
        {{$memberships->links()}}
    </section>
    <p class="ua-note">A failed or outdated Apple check means the current status is unknown. It is never counted as an active or expired membership. These checks update administrative reporting; account access continues to use the existing billing system.</p>
</div></div>
@endsection
