<p class="mb-0">{{ $sizeChart['fitting'] ?? 'Size and fit information is not available for this product.' }}
    @if(!empty($sizeChart) && isset($sizeChartId))
        <button type="button" class="btn btn-link p-0" data-bs-toggle="offcanvas" data-bs-target="#{{ $sizeChartId }}" aria-controls="{{ $sizeChartId }}">@icon('ruler')Size chart</button>
    @endif
</p>
