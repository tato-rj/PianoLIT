@php
    // Reference measurements in inches; centimeters are derived from the same values.
    $sizeRows = [
        'XXS' => [[24, 24.5], [34, 35], [7.5]],
        'XS' => [[24.5, 25], [36, 37], [7.75]],
        'S' => [[26, 26.5], [38, 39], [8.5]],
        'M' => [[26.5, 27], [40, 41], [8.75]],
        'L' => [[27, 27.5], [42, 43], [9]],
        'XL' => [[27.5, 28], [44, 46], [9.25]],
        'XXL' => [[28, 28.5], [48, 50], [9.5]],
    ];
    $formatMeasurement = function ($value, $unit) {
        if ($unit === 'cm') return rtrim(rtrim(number_format($value * 2.54, 1, '.', ''), '0'), '.');
        $whole = (int) floor($value);
        return $whole.['', ' 1/4', ' 1/2', ' 3/4'][(int) round(($value - $whole) * 4)];
    };
@endphp
<div class="offcanvas offcanvas-start shop-size-chart" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}-title">
    @include('components.layout.side-menu-header', ['titleId' => $id.'-title', 'title' => 'Fitting and size chart', 'closeLabel' => 'Close size chart'])
    <div class="offcanvas-body p-4">
        <div class="d-flex justify-content-end mb-4">
            <div>
                @include('components.forms.segmented-control', ['id' => $id.'-units', 'name' => $id.'-units', 'label' => 'Measurement units', 'options' => ['in' => 'Inches', 'cm' => 'cm'], 'value' => 'in', 'dataAttribute' => 'size-unit'])
            </div>
        </div>
        <div class="table-responsive" role="region" aria-label="Size chart measurements" tabindex="0">
            <table class="table table-striped table-borderless align-middle mb-0 shop-size-chart__table">
                <caption class="visually-hidden">T-shirt measurements in <span class="shop-size-chart__in">inches</span><span class="shop-size-chart__cm">centimeters</span></caption>
                <thead>
                    <tr><th scope="col">Size</th><th scope="col">Length</th><th scope="col">Chest</th><th scope="col">Sleeve length</th></tr>
                </thead>
                <tbody>
                    @foreach($sizeRows as $size => $measurements)
                        <tr>
                            <th scope="row">{{ $size }}</th>
                            @foreach($measurements as $range)
                                <td>
                                    @foreach(['in', 'cm'] as $unit)
                                        <span class="shop-size-chart__{{ $unit }}">{{ implode(' – ', array_map(fn ($value) => $formatMeasurement($value, $unit), $range)) }}{{ $unit === 'in' ? '″' : '' }}</span>
                                    @endforeach
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <section class="mt-4" aria-labelledby="{{ $id }}-fitting">
            <h3 class="h6 mb-3" id="{{ $id }}-fitting">Fitting</h3>
            @include('shop.storefront.components.fitting', ['sizeChartId' => null])
        </section>
        <section class="mt-4" aria-labelledby="{{ $id }}-measure">
            <h3 class="h6 mb-3" id="{{ $id }}-measure">How to measure</h3>
            <p class="mb-4">Grab a shirt you already wear and a measuring tape. Lay the shirt on a flat surface and smooth out any wrinkles before measuring.</p>
            <dl class="mb-0">
                <dt class="mb-2">Length</dt>
                <dd class="mb-4">On the front of the shirt, measure from where the shoulder meets the neck down to the bottom hem.</dd>
                <dt class="mb-2">Shoulder</dt>
                <dd class="mb-4">Measure straight across from one shoulder point to the other.</dd>
                <dt class="mb-2">Chest</dt>
                <dd class="mb-4">Measure all the way around the chest, 1″ below the armholes.</dd>
                <dt class="mb-2">Sleeve</dt>
                <dd class="mb-4">Follow the top edge of the sleeve from the shoulder seam to the end of the cuff.</dd>
                <dt class="mb-2">Neck</dt>
                <dd class="mb-0">Measure straight across the neck opening, from seam to seam where the collar meets the shoulders.</dd>
            </dl>
        </section>
    </div>
</div>
