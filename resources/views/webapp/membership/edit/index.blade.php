@extends('webapp.layouts.app')

@push('header')
<script src="https://js.stripe.com/v3/"></script>

@endpush

@section('content')
@include('webapp.layouts.header', ['title' => 'My Membership', 'subtitle' => 'Your plan, payments, and billing details, all in one place.'])
<div class="membership-management">
<section class="mb-5">
    @component('components.navigation.tab-navigation', ['id' => 'membership-tabs', 'label' => 'Membership sections', 'variant' => 'segmented', 'class' => 'mx-auto mb-4', 'items' => [
        ['target' => 'overview-tab', 'label' => 'Overview'],
        ['target' => 'update-plan-tab', 'label' => $overview['ended'] ? 'Choose plan' : 'Change plan'],
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
