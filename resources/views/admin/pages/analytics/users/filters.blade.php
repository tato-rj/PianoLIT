<form method="GET" class="ua-filters" aria-label="Activity filters">
    @isset($visitSort)<input type="hidden" name="visit_sort" value="{{ $visitSort->field }}"><input type="hidden" name="visit_order" value="{{ $visitSort->order }}">@endisset
    <label>Period<select name="range">
        @foreach(\App\Support\AdminDateRanges::LABELS as $value => $label)
        <option value="{{ $value }}" @if($filters->values['range'] == $value) selected @endif>{{ $label }}</option>
        @endforeach
    </select></label>
    @include('components.form.checkbox-menu', ['name' => 'platform', 'label' => 'Platform', 'options' => \App\Activity\Filters::PLATFORMS, 'selected' => $filters->platforms, 'allLabel' => 'All platforms'])
    @unless(!empty($profileFilters))
    <label>Audience<select name="audience">
        @foreach(['all' => 'Everyone', 'registered' => 'Registered', 'anonymous' => 'Anonymous'] as $value => $label)
        <option value="{{ $value }}" @if($filters->values['audience'] === $value) selected @endif>{{ $label }}</option>
        @endforeach
    </select></label>
    @endunless
    @include('admin.components.filters.date-range', ['label' => 'Custom range', 'periodName' => 'range', 'selectedPreset' => $filters->values['range'], 'from' => $filters->values['from'] ?? '', 'to' => $filters->values['to'] ?? '', 'defaultPreset' => '7', 'requireBoth' => true])
    <button class="ua-button" type="submit">Apply filters</button>
    <span class="ua-filter-period">@if($filters->start){{ $filters->start->format('M j, Y') }} – {{ $filters->end->copy()->subDay()->format('M j, Y') }}@else All history @endif<small>{{ config('app.timezone') }}</small></span>
</form>
