@php
    $categoryImages = [
        't-shirts' => mix('images/shop/categories/t-shirts.webp'),
        'stickers' => mix('images/shop/categories/stickers.webp'),
        'ebooks' => mix('images/shop/categories/ebooks.webp'),
        'escores' => mix('images/shop/categories/escores.webp'),
    ];
@endphp
@if(isset($categoryImages[$kind]))
    <img src="{{ asset($categoryImages[$kind]) }}" width="256" height="256" alt="" decoding="async">
@else
    <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true" focusable="false">
        <circle cx="21" cy="21" r="7" />
        <circle cx="43" cy="21" r="7" />
        <circle cx="21" cy="43" r="7" />
        <circle cx="43" cy="43" r="7" />
    </svg>
@endif
