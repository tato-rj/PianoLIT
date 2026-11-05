<button type="button" class="shop-option btn {{ !empty($selected) ? 'btn-primary' : 'btn-secondary' }}" data-option-value="{{ $option }}" aria-pressed="{{ !empty($selected) ? 'true' : 'false' }}">
    @if(!empty($swatch))<span class="shop-color-swatch" style="--swatch-color: {{ $swatch['color_code'] }}; --swatch-color-secondary: {{ $swatch['color_code2'] ?? $swatch['color_code'] }}" aria-hidden="true"></span>@endif
    {{ $option }}
</button>
