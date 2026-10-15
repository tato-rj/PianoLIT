<section class="up-card up-membership" aria-labelledby="up-membership-title">
    <div class="up-section-title"><span class="up-icon">@icon('shield-user', ['mr' => 0])</span><div><h2 id="up-membership-title">Access & membership</h2><p>Current access and saved billing information.</p></div></div>
    <div class="up-super-control"><div><strong>Super user</strong><p>Full access without a paid subscription.</p></div><label class="switch cursor-pointer"><input type="checkbox" data-user-super-status data-url="{{ route('admin.users.super-status', $user) }}" aria-label="Super user" @if($user->super_user) checked @endif><span class="slider round"></span></label></div>
    <p class="up-feedback" data-super-status-message role="status" hidden></p>
    <div class="up-divider"></div>
    @if($source)
    <dl class="up-facts">
        @include('admin.pages.users.show.fact', ['label' => 'Billing provider', 'value' => $source instanceof \App\Billing\Sources\Stripe ? 'Stripe' : 'Apple App Store'])
        @include('admin.pages.users.show.fact', ['label' => 'Membership status', 'value' => $billingStatus])
        @include('admin.pages.users.show.fact', ['label' => 'Plan', 'value' => $source->plan ? ($source->plan_name ?: $source->plan) : null])
        @include('admin.pages.users.show.fact', ['label' => 'Record started', 'value' => $formatDate($source->created_at)])
        @include('admin.pages.users.show.fact', ['label' => $source->membership_ends_at ? 'Access ends' : 'Renews / expires', 'value' => $formatDate($source->membership_ends_at ?: $source->renews_at)])
        @if($source instanceof \App\Billing\Sources\Stripe)
            @include('admin.pages.users.show.fact', ['label' => 'Customer ID', 'value' => $source->stripe_id])
            @include('admin.pages.users.show.fact', ['label' => 'Payment method', 'value' => $source->card_last_four ? ucfirst($source->card_brand ?: 'Card').' ···· '.$source->card_last_four : null])
            @if($source->paused_at)@include('admin.pages.users.show.fact', ['label' => 'Paused', 'value' => $formatDate($source->paused_at)])@endif
            @if($source->canceled_at)@include('admin.pages.users.show.fact', ['label' => 'Cancellation requested', 'value' => $formatDate($source->canceled_at)])@endif
            @if($source->ended_at)@include('admin.pages.users.show.fact', ['label' => 'Ended', 'value' => $formatDate($source->ended_at)])@endif
        @else
            @include('admin.pages.users.show.fact', ['label' => 'Original transaction', 'value' => data_get($source->latest_receipt_info, 'original_transaction_id')])
            @include('admin.pages.users.show.fact', ['label' => 'Last checked', 'value' => $formatDate(optional($appleCheck)->checked_at ?: $source->validated_at)])
            @if($appleCheck)@include('admin.pages.users.show.fact', ['label' => 'Auto-renewal', 'value' => $appleCheck->auto_renews === null ? 'Not recorded' : ($appleCheck->auto_renews ? 'On' : 'Off')])@endif
        @endif
    </dl>
    <div class="up-card-actions">
        @if($source instanceof \App\Billing\Sources\Stripe)<a class="btn btn-secondary" href="https://dashboard.stripe.com/customers/{{ rawurlencode($source->stripe_id) }}" target="_blank" rel="noopener noreferrer">Open Stripe @icon('arrow-up-right', ['mr' => 0, 'classes' => 'ms-2'])</a>@endif
        <a class="up-text-link" href="{{ route('admin.analytics.memberships', ['provider' => $source instanceof \App\Billing\Sources\Stripe ? 'stripe' : 'apple', 'search' => $user->email]) }}">Membership report @icon('arrow-up-right', ['mr' => 0])</a>
    </div>
    <p class="up-note">Billing details reflect saved records. Account removal does not cancel a provider subscription.</p>
    @else
    <div class="up-empty up-empty--compact">@icon('credit-card', ['mr' => 0])<h3>{{ $user->membership ? 'Membership record unavailable' : 'No subscription' }}</h3><p>{{ $user->membership ? 'Review the membership report before changing this account.' : 'This account has no billing membership on file.' }}</p></div>
    @endif
</section>
