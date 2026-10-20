<div class="pianolit-date-picker" data-date-picker data-min-date="{{ $minDate ?? '' }}" data-blocked-dates="{{ json_encode($blockedDates ?? []) }}">
    @include('components.form.input', ['type' => 'text', 'readonly' => true, 'autocomplete' => 'off', 'placeholder' => 'Choose a date'])
    <div data-date-picker-calendar aria-label="{{ $label ?? 'Choose a date' }}"></div>
</div>
@once
    <script src="{{ mix('js/components/date-picker.js') }}" defer></script>
@endonce
