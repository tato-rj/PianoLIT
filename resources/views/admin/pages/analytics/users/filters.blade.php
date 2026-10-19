<form method="GET" class="ua-filters" aria-label="Activity filters">
    @isset($visitSort)<input type="hidden" name="visit_sort" value="{{ $visitSort->field }}"><input type="hidden" name="visit_order" value="{{ $visitSort->order }}">@endisset
    @include('admin.components.filters.select', ['label' => 'Period', 'name' => 'range', 'options' => \App\Support\AdminDateRanges::LABELS, 'value' => $filters->values['range']])
    @include('components.form.checkbox-menu', ['name' => 'platform', 'label' => 'Platform', 'options' => \App\Activity\Filters::PLATFORMS, 'selected' => $filters->platforms, 'allLabel' => 'All platforms'])
    @unless(!empty($profileFilters))
    @include('admin.components.filters.select', ['label' => 'Audience', 'name' => 'audience', 'options' => ['all' => 'Everyone', 'registered' => 'Registered', 'anonymous' => 'Anonymous'], 'value' => $filters->values['audience']])
    @endunless
    @include('admin.components.filters.date-range', ['label' => 'Custom range', 'fieldLabel' => 'Dates', 'showLabel' => true, 'periodName' => 'range', 'selectedPreset' => $filters->values['range'], 'from' => $filters->values['from'] ?? '', 'to' => $filters->values['to'] ?? '', 'defaultPreset' => '7', 'requireBoth' => true])
    <button class="ua-button" type="submit">Apply filters</button>
    <span class="ua-filter-period">@if($filters->start){{ $filters->start->format('M j, Y') }} – {{ $filters->end->copy()->subDay()->format('M j, Y') }}@else All history @endif</span>
</form>
