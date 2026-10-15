@extends('webapp.layouts.app')

@push('header')
<script src="https://js.stripe.com/v3/"></script>

<style type="text/css">
#update-plan-tab .btn-raw:not(.selected-plan) {
	opacity: .4;
}
</style>
@endpush

@section('content')
@include('webapp.layouts.header', ['title' => 'My Membership', 'subtitle' => 'Manage your account details here'])
@if($source->isEnded() || in_array($source->status, ['canceled', 'incomplete_expired'], true))
    <div class="alert alert-warning">Your membership has ended. <a href="{{ route('webapp.membership.pricing') }}">Choose a plan to subscribe again</a>. Your invoices remain available below.</div>
@elseif(in_array($source->status, ['past_due', 'unpaid', 'incomplete'], true))
    <div class="alert alert-warning" role="status">Your membership needs a payment. Update your card in Billing, then complete the open invoice in Invoices.
        @if($source->payment_url)<a href="{{ $source->payment_url }}" target="_blank" rel="noopener noreferrer">Complete payment</a>@endif
    </div>
@endif
@if($source->pending_plan && $source->plan_changes_at)
    <div class="alert alert-info">Your {{ ucfirst($source->pending_plan) }} plan starts on {{ $source->plan_changes_at->toFormattedDateString() }}. Your current plan remains in effect until then.</div>
@endif

<section class=" mb-5">
	<ul class="nav nav-tabs nav-responsive mb-4" id="membership-tabs" role="tablist">
		<li class="nav-item">
			<a class="nav-link active" data-bs-toggle="tab" href="#overview-tab">Overview</a>
		</li>
		<li class="nav-item">
			<a class="nav-link" data-bs-toggle="tab" href="#update-plan-tab">Change Plan</a>
		</li>
		<li class="nav-item">
			<a class="nav-link" data-bs-toggle="tab" href="#update-card-tab">Billing</a>
		</li>
		<li class="nav-item">
			<a class="nav-link" data-bs-toggle="tab" href="#invoices-tab">Invoices</a>
		</li>
	</ul>
	<div class="tab-content" id="membership-panels">
		@include('webapp.membership.edit.sections.overview')
		@include('webapp.membership.edit.sections.update-plan')
		@include('webapp.membership.edit.sections.billing')
		@include('webapp.membership.edit.sections.invoices')
	</div>
</section>

@include('webapp.membership.pricing.faq')
@endsection

@push('scripts')
<script src="{{ mix('js/views/membership-billing.js') }}" defer></script>
@endpush
