@if($unavailable ?? false)
    @component('components.feedback.tooltip', ['text' => 'This will be available soon', 'trigger' => 'hover'])
        @include('shop.storefront.components.option', ['unavailable' => false, 'disabled' => true, 'selected' => false, 'comingSoon' => true])
    @endcomponent
@else
<button type="button" class="shop-option btn btn-secondary {{ !empty($small) ? 'btn-sm' : '' }} {{ !empty($selected) ? 'border-primary' : '' }} {{ !empty($disabled) ? 'pe-none' : '' }}" data-option-value="{{ $option }}" aria-pressed="{{ !empty($selected) ? 'true' : 'false' }}" @if($disabled ?? false) disabled @endif @if($comingSoon ?? false) aria-label="{{ $option }}. This will be available soon" @endif>
    @if(!empty($swatch))<span class="shop-color-swatch" style="--swatch-color: {{ $swatch['color_code'] }}; --swatch-color-secondary: {{ $swatch['color_code2'] ?? $swatch['color_code'] }}" aria-hidden="true"></span>@endif
    {{ $option }}
</button>
@endif
