@if($collapsible)
    @component('components.layout.accordion', ['id' => $id.'-accordion', 'variant' => 'minimal'])
        @component('components.layout.accordion-item', ['id' => $id, 'parentId' => 'checkout-sections', 'title' => $title, 'indicator' => 'plus-minus', 'open' => true])
            {{ $slot }}
        @endcomponent
    @endcomponent
@else
    {{ $slot }}
@endif
