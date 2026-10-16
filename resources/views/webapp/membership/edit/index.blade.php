@extends('webapp.layouts.app')

@push('header')
<script src="https://js.stripe.com/v3/"></script>

@endpush

@section('content')
@include('webapp.layouts.header', ['title' => 'My Membership', 'subtitle' => 'Your plan, payments, and billing details, all in one place.'])
<div class="membership-management">
@if($overview['ended'])
    <div class="alert alert-warning">Your membership has ended. <a href="{{ route('webapp.membership.pricing') }}">Choose a plan to subscribe again</a>. Your invoices remain available below.</div>
@elseif(in_array($source->status, ['past_due', 'unpaid', 'incomplete'], true))
    <div class="alert alert-warning" role="status">Your membership needs a payment. Update your card in Billing, then complete the open invoice in Invoices.
        @if($source->payment_url)<a href="{{ $source->payment_url }}" target="_blank" rel="noopener noreferrer">Complete payment</a>@endif
    </div>
@endif
@if($source->pending_plan && $source->plan_changes_at)
    <div class="alert alert-info">Your {{ ucfirst($source->pending_plan) }} plan starts on {{ $source->plan_changes_at->toFormattedDateString() }}. Your current plan remains in effect until then.</div>
@endif

<section class="mb-5">
    @component('components.tab-navigation', ['id' => 'membership-tabs', 'label' => 'Membership sections', 'variant' => 'segmented', 'class' => 'mx-auto mb-5', 'items' => [
        ['target' => 'overview-tab', 'label' => 'Overview'],
        ['target' => 'update-plan-tab', 'label' => 'Change plan'],
        ['target' => 'update-card-tab', 'label' => 'Billing'],
        ['target' => 'invoices-tab', 'label' => 'Invoices'],
    ]])
	<div class="tab-content" id="membership-panels">
		@include('webapp.membership.edit.sections.overview')
		@include('webapp.membership.edit.sections.update-plan')
		@include('webapp.membership.edit.sections.billing')
		@include('webapp.membership.edit.sections.invoices')
	</div>
    @endcomponent
</section>
</div>

@include('webapp.membership.pricing.faq')
@endsection

@push('scripts')
<script src="{{ mix('js/views/membership-billing.js') }}" defer></script>
@endpush
