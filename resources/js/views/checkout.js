const stripe = Stripe($('#payment-form').data('key'));

$(document).ready(function() {
  // Create an instance of Elements.
  const elements = stripe.elements();
  const card = elements.create('card', {hidePostalCode: true});
  const cardErrors = document.getElementById('card-errors');

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
    let button = this.querySelector('button');
    event.preventDefault();
    
    disableSubmitButton(button);

    stripe.createToken(card).then(function(result) {
      if (result.error) {
        cardErrors.textContent = result.error.message;
        enableSubmitButton(button);
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

  function disableSubmitButton(button) {
    button.disabled = true;
  }

  function enableSubmitButton(button) {
    button.disabled = false;
  }
});

$('input[name="coupon"]').on('keyup', function() {
  $('#coupon-feedback').text('');
});

$('input[name="coupon"]').on('blur', function() {
  let $input = $(this);
  let $feedback = $input.closest('.form-group').find('.coupon-feedback');
  let $validation = $input.closest('.form-group').find('.load-validation');
  let $submitButton = $input.closest('form').find('button');

  if ($(this).val().length > 4) {
    $validation.fadeIn('fast');
    $submitButton.disable();

    axios.get($feedback.data('url'), {params: {coupon: $input.val()}})
        .then(function(response) {
          if (response.data.isValid) {
            $feedback.removeClass('invalid-feedback').addClass('valid-feedback');
          } else {
            $feedback.addClass('invalid-feedback').removeClass('valid-feedback');
          }

          $feedback.text(response.data.message).show();
        })
        .catch(function(response) {
          $feedback.addClass('invalid-feedback').removeClass('valid-feedback').text(response.data.message).show();
        })
        .then(function() {
          $validation.fadeOut('fast');
          $submitButton.enable();
        });
      }
});

