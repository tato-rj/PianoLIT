<fieldset class="membership-card-fields">
    <legend class="visually-hidden">@icon('credit-card') Credit or debit card</legend>
    <div class="membership-field">
        @include('components.forms.label', ['id' => $prefix.'-card-number', 'label' => 'Card number'])
        <div id="{{ $prefix }}-card-number" class="form-control membership-stripe-field" data-stripe-field="cardNumber" tabindex="-1"></div>
    </div>
    <div class="membership-field">
        @input(['bag' => 'default', 'id' => $prefix.'-card-name', 'name' => 'cardholder', 'label' => 'Name on card', 'autocomplete' => 'cc-name', 'limit' => 255, 'value' => $cardholder ?? '', 'placeholder' => 'Name as it appears on your card'])
    </div>
    <div class="membership-card-row">
        <div class="membership-field">
            @include('components.forms.label', ['id' => $prefix.'-card-expiry', 'label' => 'Expiration date'])
            <div id="{{ $prefix }}-card-expiry" class="form-control membership-stripe-field" data-stripe-field="cardExpiry"></div>
        </div>
        <div class="membership-field">
            @include('components.forms.label', ['id' => $prefix.'-card-cvc', 'label' => 'Security code'])
            <div id="{{ $prefix }}-card-cvc" class="form-control membership-stripe-field" data-stripe-field="cardCvc"></div>
        </div>
    </div>
    <div class="membership-field">
        @input(['bag' => 'default', 'id' => $prefix.'-postal-code', 'name' => 'postal_code', 'label' => 'Billing ZIP / postal code (optional)', 'autocomplete' => 'billing postal-code', 'limit' => 20, 'required' => false, 'placeholder' => 'ZIP / postal code'])
    </div>
</fieldset>
