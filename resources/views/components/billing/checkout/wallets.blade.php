<section class="checkout-wallets" data-checkout-wallets data-wallet-state="loading" aria-label="Express payment" hidden>
    <p class="text-center text-muted mb-0 small">– Express checkout –</p>
    <div class="px-0" data-wallet-mount></div>
    @if(($divider ?? 'Or') !== '')<p class="checkout-payment-divider"><span>{{ $divider ?? 'Or' }}</span></p>@endif
</section>
