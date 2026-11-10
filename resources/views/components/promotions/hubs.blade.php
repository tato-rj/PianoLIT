@foreach($hubs ?? app(\App\Services\Blog\PostPromotions::class)->cards(\App\Services\Blog\PostPromotions::DEFAULT_CHOICE) as $hub)
    @include('components.cards.promotion', $hub)
@endforeach
