<section class="mb-4" aria-labelledby="local-shipment-heading">
    <h2 class="h4" id="local-shipment-heading">Local shipment simulator</h2>
    <p>Simulate Printful shipping updates for this test order. Emails are captured below as previews; nothing is sent to Printful or an email provider.</p>
    <div class="d-flex flex-wrap gap-3 align-items-center">
        <form method="POST" action="{{ route('admin.shop-orders.simulation.event', $order->id) }}" class="d-flex flex-wrap gap-3">
            @csrf
            <button class="btn btn-dark" type="submit" name="event" value="shipment_sent">@icon('refresh-cw') {{ $order->shipments->contains('printful_id', '1') ? 'Replay shipped' : 'Simulate shipped' }}</button>
            <button class="btn btn-dark" type="submit" name="event" value="shipment_delivered" @if(!$order->shipments->contains('printful_id', '1')) disabled @endif>@icon('check') {{ $order->shipments->contains('status', 'delivered') ? 'Replay delivered' : 'Simulate delivered' }}</button>
        </form>
        <a class="btn btn-secondary" href="{{ app(\App\Services\Shop\OrderTracking::class)->url($order) }}" target="_blank" rel="noopener noreferrer">View customer tracking @icon('external-link')</a>
    </div>
    <p class="text-muted mt-3 mb-0">Ship first, then mark delivered. Replay an event to check that it does not create another package or email.</p>
</section>
