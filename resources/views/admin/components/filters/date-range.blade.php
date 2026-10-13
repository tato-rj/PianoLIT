@php
    $dateId = 'admin-date-range-'.\Illuminate\Support\Str::uuid();
    $datePresets = \App\Support\AdminDateRanges::presets();
    $dateFrom = $from ?? '';
    $dateTo = $to ?? '';
    $dateLabel = $label ?? 'Custom range';
    $datePlaceholder = $placeholder ?? 'Custom range';
    $dateSelected = isset($selectedPreset) ? (string) $selectedPreset : ($dateFrom || $dateTo ? 'custom' : '');
    if (empty($periodName) && $dateFrom && $dateTo) {
        foreach ($datePresets as $key => $preset) if ($preset['from'] === $dateFrom && $preset['to'] === $dateTo) { $dateSelected = (string) $key; break; }
    }
    $dateSummary = empty($periodName) && $dateSelected && $dateSelected !== 'custom' ? $datePresets[$dateSelected]['label'] : ($dateSelected === 'custom' ? 'Custom range' : $datePlaceholder);
@endphp
<details class="admin-date-range" data-admin-date-range data-period-name="{{ $periodName ?? '' }}" data-current-preset="{{ $dateSelected }}" data-default-preset="{{ $defaultPreset ?? '' }}" data-required="{{ !empty($requireBoth) ? 'true' : 'false' }}" data-max-days="{{ $maxDays ?? '' }}" data-placeholder="{{ $datePlaceholder }}" data-label="{{ $dateLabel }}">
    @if(!empty($showLabel))<span class="admin-date-range-label">{{ $dateLabel }}</span>@endif
    <summary aria-haspopup="dialog" aria-controls="{{ $dateId }}" aria-expanded="false" aria-label="{{ $dateLabel }}: {{ $dateSummary }}">
        <span class="admin-date-range-calendar" aria-hidden="true">@icon('calendar', ['mr' => 0])</span><span data-date-range-summary>{{ $dateSummary }}</span>@icon('chevron-down', ['mr' => 0])
    </summary>
    <input type="hidden" name="{{ $fromName ?? 'from' }}" value="{{ $dateFrom }}" data-date-range-from>
    <input type="hidden" name="{{ $toName ?? 'to' }}" value="{{ $dateTo }}" data-date-range-to>
    <div class="admin-date-range-panel" id="{{ $dateId }}" role="dialog" aria-modal="false" aria-labelledby="{{ $dateId }}-title">
        <nav class="admin-date-range-presets" aria-label="{{ $dateLabel }} presets">
            @foreach($datePresets as $key => $preset)
            <button type="button" data-date-preset="{{ $key }}" data-from="{{ $preset['from'] }}" data-to="{{ $preset['to'] }}" aria-pressed="{{ $dateSelected === (string) $key ? 'true' : 'false' }}">{{ $preset['label'] }}</button>
            @endforeach
            <button type="button" data-date-preset="custom" aria-pressed="{{ $dateSelected === 'custom' ? 'true' : 'false' }}">Custom range</button>
        </nav>
        <div class="admin-date-range-editor">
            <h3 id="{{ $dateId }}-title">{{ $dateLabel }}</h3>
            <div class="admin-date-range-fields">
                <label for="{{ $dateId }}-from">From<input type="date" id="{{ $dateId }}-from" value="{{ $dateFrom }}" data-date-draft-from></label>
                <label for="{{ $dateId }}-to">To<input type="date" id="{{ $dateId }}-to" value="{{ $dateTo }}" data-date-draft-to></label>
            </div>
            <p class="admin-date-range-error" data-date-range-error role="alert" hidden></p>
            <div class="admin-date-range-footer">
                <button type="button" class="admin-date-range-clear" data-date-range-clear>Clear</button>
                <button type="button" class="admin-date-range-apply" data-date-range-apply>Apply</button>
            </div>
        </div>
    </div>
</details>
