<div class="shop-quantity {{ ($compact ?? false) ? 'shop-quantity--compact' : '' }}" data-quantity>
    <button type="button" aria-label="Decrease quantity{{ isset($productName) ? ' for '.$productName : '' }}" data-quantity-change="-1">@icon('minus')</button>
    <input type="number" name="quantity" value="{{ $quantity ?? 1 }}" min="1" max="10" step="1" required aria-label="Quantity{{ isset($productName) ? ' for '.$productName : '' }}">
    <button type="button" aria-label="Increase quantity{{ isset($productName) ? ' for '.$productName : '' }}" data-quantity-change="1">@icon('plus')</button>
</div>
