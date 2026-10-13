<form method="GET" class="ua-filters" aria-label="Activity filters">
    @if(isset($visitSort) && $visitSort->value !== 'newest')<input type="hidden" name="visit_sort" value="{{ $visitSort->value }}">@endif
    <label>Period<select name="range">
        @foreach(['today' => 'Today', 'yesterday' => 'Yesterday', '7' => 'Last 7 days', '30' => 'Last 30 days', 'custom' => 'Custom dates'] as $value => $label)
        <option value="{{ $value }}" @if($filters->values['range'] == $value) selected @endif>{{ $label }}</option>
        @endforeach
    </select></label>
    <label>Platform<select name="platform">
        @foreach(['all' => 'All platforms', 'web' => 'Website', 'webapp' => 'Webapp', 'app' => 'iOS'] as $value => $label)
        <option value="{{ $value }}" @if($filters->values['platform'] === $value) selected @endif>{{ $label }}</option>
        @endforeach
    </select></label>
    @unless(!empty($profileFilters))
    <label>Audience<select name="audience">
        @foreach(['all' => 'Everyone', 'registered' => 'Registered', 'anonymous' => 'Anonymous'] as $value => $label)
        <option value="{{ $value }}" @if($filters->values['audience'] === $value) selected @endif>{{ $label }}</option>
        @endforeach
    </select></label>
    @endunless
    <details class="ua-custom-dates" @if($filters->values['range'] === 'custom') open @endif><summary>Custom dates</summary>
        <label>From<input type="date" name="from" value="{{ $filters->values['from'] ?? '' }}"></label>
        <label>To<input type="date" name="to" value="{{ $filters->values['to'] ?? '' }}"></label>
        <small>Select “Custom dates” as the period.</small>
    </details>
    <button class="ua-button" type="submit">Apply filters</button>
    <span class="ua-filter-period">{{ $filters->start->format('M j') }} – {{ $filters->end->copy()->subDay()->format('M j, Y') }}<small>{{ config('app.timezone') }}</small></span>
</form>
