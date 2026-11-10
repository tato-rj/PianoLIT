@php
    $promotionHubs = $hubs ?? app(\App\Services\Blog\PostPromotions::class)->cards(\App\Services\Blog\PostPromotions::DEFAULT_CHOICE);
@endphp
@if(count($promotionHubs))
    <div class="hub-promotions {{ ($persistent ?? false) ? 'hub-promotions--persistent' : '' }} {{ count($promotionHubs) > 1 ? 'hub-promotions--multiple' : '' }}" @if($preview ?? false) data-ds-preview @elseif($persistent ?? false) data-hub-promotions @endif>
        @foreach($promotionHubs as $hub)
            @include('components.cards.promotion', array_merge($hub, ['preview' => false]))
        @endforeach
    </div>
    @if(($persistent ?? false) && !($preview ?? false))
        @once
            @push('scripts')
                <script src="{{ mix('js/components/hub-promotions.js') }}" defer></script>
            @endpush
        @endonce
    @endif
@endif
