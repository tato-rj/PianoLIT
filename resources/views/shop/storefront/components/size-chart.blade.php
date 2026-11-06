<div class="offcanvas offcanvas-start shop-size-chart" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}-title">
    @include('components.layout.side-menu-header', ['titleId' => $id.'-title', 'title' => 'Fitting and size chart', 'closeLabel' => 'Close size chart'])
    <div class="offcanvas-body p-4">
        <p class="mb-3">{{ $sizeChart['name'] }} · Garment measurements</p>
        <div class="d-flex justify-content-end mb-4">
            <div>
                @include('components.forms.segmented-control', ['id' => $id.'-units', 'name' => $id.'-units', 'label' => 'Measurement units', 'options' => ['in' => 'Inches', 'cm' => 'cm'], 'value' => 'in', 'dataAttribute' => 'size-unit'])
            </div>
        </div>
        <div class="table-responsive" role="region" aria-label="Size chart measurements" tabindex="0">
            <table class="table table-striped table-borderless align-middle mb-0 shop-size-chart__table">
                <caption class="visually-hidden">{{ $sizeChart['name'] }} garment measurements in <span class="shop-size-chart__in">inches</span><span class="shop-size-chart__cm">centimeters</span></caption>
                <thead>
                    <tr><th scope="col">Size</th><th scope="col">Length</th><th scope="col">Width</th><th scope="col">Sleeve length</th></tr>
                </thead>
                <tbody>
                    @foreach($sizeChart['rows'] as $size => $measurements)
                        <tr>
                            <th scope="row">{{ $size }}</th>
                            @for($column = 0; $column < 3; $column++)
                                <td>
                                    @foreach(['in', 'cm'] as $unit)
                                        <span class="shop-size-chart__{{ $unit }}">{{ $measurements[$unit][$column] }}{{ $unit === 'in' ? '″' : '' }}</span>
                                    @endforeach
                                </td>
                            @endfor
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="mt-3 mb-0">Product measurements may vary by up to 2″ (5 cm).</p>
        <p class="mt-2 mb-0"><a href="{{ $sizeChart['source'] }}" target="_blank" rel="noopener noreferrer">Printful size guide</a></p>
        <section class="mt-4" aria-labelledby="{{ $id }}-fitting">
            <h3 class="h6 mb-3" id="{{ $id }}-fitting">Fitting</h3>
            @include('shop.storefront.components.fitting', ['sizeChartId' => null, 'sizeChart' => $sizeChart])
        </section>
        <section class="mt-4" aria-labelledby="{{ $id }}-measure">
            <h3 class="h6 mb-3" id="{{ $id }}-measure">How to measure</h3>
            <p class="mb-4">Lay a T-shirt flat and compare its measurements with the chart.</p>
            <dl class="mb-0">
                <dt class="mb-2">Length</dt>
                <dd class="mb-4">Measure from the highest point of the shoulder beside the collar down to the bottom hem.</dd>
                <dt class="mb-2">Width</dt>
                <dd class="mb-4">Measure straight across from the seam under one sleeve to the seam under the other. This is the flat garment width.</dd>
                <dt class="mb-2">Sleeve length</dt>
                <dd class="mb-0">Start at the center back of the collar. Measure along the top seam to the shoulder, then continue along the sleeve to its hem.</dd>
            </dl>
        </section>
    </div>
</div>
