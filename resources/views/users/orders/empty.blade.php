<section class="order-history-empty text-center mx-auto py-3 py-md-4" aria-labelledby="orders-empty-title">
    @include('users.orders.illustration')
    <h2 id="orders-empty-title" class="h4 mt-4 mb-2">A little piano, delivered</h2>
    <p class="text-muted mb-4">Find your next favorite in the Shop.<br>We’ll keep track of it here.</p>
    <a class="btn btn-primary" href="{{ route('shop.home') }}">Explore the Shop @icon('arrow-right')</a>
</section>
