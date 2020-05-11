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

<section class=" mb-5">
	<ul class="nav nav-tabs mb-4" id="membership-tabs" role="tablist">
		<li class="nav-item">
			<a class="nav-link active" data-toggle="tab" href="#overview-tab">Overview</a>
		</li>
		<li class="nav-item">
			<a class="nav-link" data-toggle="tab" href="#update-plan-tab">Change Plan</a>
		</li>
		<li class="nav-item">
			<a class="nav-link" data-toggle="tab" href="#update-card-tab">Billing</a>
		</li>
		<li class="nav-item">
			<a class="nav-link" data-toggle="tab" href="#invoices-tab">Invoices</a>
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
<script type="text/javascript">
$('button[name="plan"]').on('click', function() {
	let $plan = $(this);
	let $check = $('#plan-check').detach();
	$plan.addClass('selected-plan').siblings('button').removeClass('selected-plan');
	$plan.append($check);
	$('#update-plan-action input[name="plan"]').val($plan.data('name'));

	$('#update-plan-action > p').hide();
	$('#update-plan-action > form').show();
});
</script>
<script type="text/javascript">
$('#faq-accordion').on('show.bs.collapse', function (event) {
  $(event.target).siblings('div').find('i').removeClass('fa-plus').addClass(' fa-minus');
});

$('#faq-accordion').on('hide.bs.collapse', function (event) {
  $(event.target).siblings('div').find('i').removeClass('fa-minus').addClass(' fa-plus');
});
</script>
<script type="text/javascript">
const stripe = Stripe("{{config('services.stripe.key')}}");

$(document).ready(function() {
  // Create an instance of Elements.
  const elements = stripe.elements();
  const card = elements.create('card', {hidePostalCode: true});
  const cardErrors = document.getElementById('card-errors');

  const cardHolderName = document.getElementById('card-holder-name');
  const nameErrors = document.getElementById('name-errors');

  const formButton = document.getElementById('card-button');
  const buttonIcon = formButton.querySelector('i');

  card.mount('#card-element');

  /////////////////////
  // VALIDATE INPUTS //
  /////////////////////
  card.addEventListener('change', function(event) {
    cardErrors.textContent = event.error ? event.error.message : '';
  });

  cardHolderName.addEventListener('change', function(event) {
    nameErrors.textContent = '';
  });

  /////////////////
  // SUBMIT FORM //
  /////////////////
  var form = document.getElementById('update-card-form');
  form.addEventListener('submit', function(event) {
    event.preventDefault();
    
    disableSubmitButton();

    stripe.createToken(card).then(function(result) {
      if (result.error) {
        cardErrors.textContent = result.error.message;
          enableSubmitButton();
      } else {
        if (! cardHolderName.value) {
          nameErrors.textContent = 'The name shown on the card is required';
          enableSubmitButton();
        } else {
          nameErrors.textContent = '';
          stripeTokenHandler(result.token);
        }
      }
    });
  });

  function stripeTokenHandler(token) {
    var form = document.getElementById('update-card-form');
    var hiddenInput = document.createElement('input');
    
    hiddenInput.setAttribute('type', 'hidden');
    hiddenInput.setAttribute('name', 'stripeToken');
    hiddenInput.setAttribute('value', token.id);
    form.appendChild(hiddenInput);
    
    form.submit();
  }

  function disableSubmitButton() {
    formButton.disabled = true;
    buttonIcon.classList.toggle('fa-lock');
    buttonIcon.classList.toggle('spinner-border');
  }

  function enableSubmitButton() {
    formButton.disabled = false;
    buttonIcon.classList.toggle('fa-lock');
    buttonIcon.classList.toggle('spinner-border');
  }
});
</script>
@endpush