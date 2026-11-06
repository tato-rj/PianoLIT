<div class="shop-quantity {{ !empty($compact) ? 'shop-quantity--compact' : '' }}" data-quantity>
    <button class="btn btn-raw btn-sm" type="button" aria-label="Decrease quantity{{ isset($productName) ? ' for '.$productName : '' }}" data-quantity-change="-1">@icon('minus', ['mr' => 0])</button>
    <input class="form-control {{ !empty($compact) ? 'form-control-sm' : '' }}" type="number" name="quantity" value="{{ $quantity ?? 1 }}" min="1" max="10" step="1" required aria-label="Quantity{{ isset($productName) ? ' for '.$productName : '' }}">
    <button class="btn btn-raw btn-sm" type="button" aria-label="Increase quantity{{ isset($productName) ? ' for '.$productName : '' }}" data-quantity-change="1">@icon('plus', ['mr' => 0])</button>
</div>
