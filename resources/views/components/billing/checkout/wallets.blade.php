<section class="checkout-wallets" data-checkout-wallets data-wallet-state="loading" aria-label="Express payment" hidden>
    @if(!empty($heading))<p class="text-center text-muted mb-3">{{ $heading }}</p>@endif
    <div data-wallet-mount></div>
    @if(($divider ?? 'Or pay by card') !== '')<p class="checkout-payment-divider"><span>{{ $divider ?? 'Or pay by card' }}</span></p>@endif
</section>
