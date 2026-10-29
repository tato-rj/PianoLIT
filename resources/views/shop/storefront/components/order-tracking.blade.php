<section class="my-4" aria-labelledby="order-tracking-title">
    <h2 id="order-tracking-title">{{ app(\App\Services\Shop\OrderTracking::class)->status($order) }}</h2>
    @forelse($order->shipments as $shipment)
        <article class="py-4 border-bottom">
            <h3 class="h4 mb-3">Package {{ $loop->iteration }} · {{ ucfirst($shipment->status) }}</h3>
            @if($shipment->carrier)<p class="mb-2">Carrier: {{ $shipment->carrier }}@if($shipment->service) · {{ $shipment->service }}@endif</p>@endif
            @if($shipment->shipped_at)<p class="mb-2">Shipped {{ $shipment->shipped_at->format('F j, Y') }}</p>@endif
            @if($shipment->delivered_at)<p class="mb-2">Delivered {{ $shipment->delivered_at->format('F j, Y') }}</p>@endif
            @if($shipment->status !== 'delivered' && $shipment->estimated_from && $shipment->estimated_to)<p class="mb-2">Estimated delivery: {{ $shipment->estimated_from->format('F j') }}–{{ $shipment->estimated_to->format('F j, Y') }}</p>@endif
            @if($shipment->tracking_number)<p class="text-break mb-3">Tracking number: {{ $shipment->tracking_number }}</p>@endif
            @foreach($shipment->items as $item)<p class="mb-2">{{ $item['name'] }} · {{ $item['option'] }} × {{ $item['quantity'] }}</p>@endforeach
            @if($shipment->tracking_url)
                <a class="shop-button mt-3" href="{{ $shipment->tracking_url }}" target="_blank" rel="noopener noreferrer">Track this package @icon('arrow-up-right')</a>
            @else
                <p class="text-muted mb-0">The carrier hasn’t provided a tracking link yet.</p>
            @endif
            @if($shipment->events)
                <ul class="mt-4 mb-0 ps-3">
                    @foreach(collect($shipment->events)->sortByDesc('at')->take(3) as $event)
                        <li class="mb-2">{{ $event['description'] }}@if($event['at']) <span class="text-muted">· {{ \Carbon\Carbon::parse($event['at'])->format('M j, Y') }}</span>@endif</li>
                    @endforeach
                </ul>
            @endif
        </article>
    @empty
        <p>Tracking details will appear here when your package ships. We’ll also send you a shipping email.</p>
    @endforelse
    @if($order->shipments->isNotEmpty())<p class="mt-3 text-muted">Items may arrive in separate packages. Carrier tracking can take a little time to update.</p>@endif
</section>
