@extends('webapp.layouts.app')

@push('header')
<script src="https://js.stripe.com/v3/"></script>
@endpush

@section('content')
@include('webapp.layouts.header', ['title' => 'Checkout', 'subtitle' => 'Almost there! Please review carefully the details below.'])

<section class="row">
  @include('webapp.membership.checkout.summary')

  @include('webapp.membership.checkout.form')
</section>

@endsection

@push('scripts')
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
  var form = document.getElementById('payment-form');
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
    var form = document.getElementById('payment-form');
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

$('input[name="coupon"]').on('blur', function() {
  axios.get("{{route('webapp.membership.validate-coupon')}}", {params: {coupon: $(this).val()}})
      .then(function(response) {
        console.log(response.data);
      })
      .catch(function(response) {
        console.log(response.data);
      });
});
</script>
@endpush