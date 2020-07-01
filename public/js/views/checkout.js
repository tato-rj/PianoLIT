const stripe = Stripe($('#payment-form').data('key'));

$(document).ready(function() {
  // Create an instance of Elements.
  const elements = stripe.elements();
  const card = elements.create('card', {hidePostalCode: true});
  const cardErrors = document.getElementById('card-errors');

  const formButton = document.getElementById('card-button');
  const buttonIcon = formButton.querySelector('i');

  card.mount('#card-element');

  /////////////////////
  // VALIDATE INPUTS //
  /////////////////////
  card.addEventListener('change', function(event) {
    cardErrors.textContent = event.error ? event.error.message : '';
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
        stripeTokenHandler(result.token);
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

$('input[name="coupon"]').on('keyup', function() {
  $('#coupon-feedback').text('');
});

$('input[name="coupon"]').on('blur', function() {
  let $input = $('#coupon-feedback');

  if ($(this).val().length > 4) {
    $('#card-button').disable();
    
    axios.get($input.data('url'), {params: {coupon: $(this).val()}})
        .then(function(response) {
          if (response.data.isValid) {
            $input.removeClass('invalid-feedback').addClass('valid-feedback');
          } else {
            $input.addClass('invalid-feedback').removeClass('valid-feedback');
          }

          $input.text(response.data.message).show();
        })
        .catch(function(response) {
          $input.addClass('invalid-feedback').removeClass('valid-feedback').text(response.data.message).show();
        })
        .then(function() {
          $('#card-button').enable();
        });
      }
});