@php
    $failedPick = $failedFreePick ?? null;
    $scheduleOptions = $freePickScheduleOptions ?? app(\App\Services\FreePickSchedule::class)->options();
    $savedSchedule = is_string(old('scheduled_at')) ? old('scheduled_at') : '';
    $savedDate = preg_match('/^(\d{4}-\d{2}-\d{2})T/', $savedSchedule, $match) ? $match[1] : '';
    $savedHour = preg_match('/T((?:[01][0-9]|2[0-3]):00)$/D', $savedSchedule, $match) ? $match[1] : '08:00';
    $hours = [];
    foreach (range(0, 23) as $hour) $hours[sprintf('%02d:00', $hour)] = ($hour % 12 ?: 12).($hour < 12 ? 'am' : 'pm');
@endphp
@component('components.dialogs.modal', ['id' => 'freepick-schedule-modal', 'header' => 'Schedule Freepick', 'centered' => true])
    @slot('body')
        <form method="POST" data-freepick-form data-now="{{ $scheduleOptions['now'] }}" data-timezone="{{ $scheduleOptions['timezone'] }}"
              @if($failedPick) action="{{ route('admin.pieces.schedule', ['piece' => $failedPick] + $returnFilters) }}" @endif
              data-restore="{{ $failedPick ? 'true' : 'false' }}">
            @csrf
            <input type="hidden" name="scheduled_at" value="{{ $savedSchedule }}">
            @if($errors->getBag('freepick')->any())
                <div class="alert alert-red" role="alert" data-freepick-error>{{ $errors->getBag('freepick')->first() }}</div>
            @endif
            <p class="font-weight-bold mb-4" data-freepick-title>{{ $failedPick ? $failedPick->long_name : '' }}</p>
            <fieldset class="mb-4">
                <legend class="small text-muted mb-2">When should this piece become the Freepick?</legend>
                @include('components.forms.checkbox', ['type' => 'radio', 'id' => 'freepick-now', 'name' => 'mode', 'value' => 'now', 'label' => 'Make it Freepick immediately', 'checked' => !$failedPick || old('mode') !== 'later'])
                @include('components.forms.checkbox', ['type' => 'radio', 'id' => 'freepick-later', 'name' => 'mode', 'value' => 'later', 'label' => 'Schedule for later', 'checked' => $failedPick && old('mode') === 'later'])
                @include('components.forms.error', ['field' => 'mode', 'bag' => 'freepick'])
            </fieldset>
            <div data-freepick-calendar @if(!$failedPick || old('mode') !== 'later') hidden @endif>
                <div class="row">
                    <div class="col-sm-8 mb-3">
                        @include('components.forms.date-picker', ['name' => 'scheduled_date', 'id' => 'freepick-date', 'label' => 'Date', 'bag' => 'freepick', 'value' => $savedDate, 'minDate' => substr($scheduleOptions['now'], 0, 10), 'blockedDates' => $scheduleOptions['blockedDates'], 'disabled' => !$failedPick || old('mode') !== 'later', 'describedBy' => 'freepick-timezone'])
                    </div>
                    <div class="col-sm-4">
                        @select(['name' => 'scheduled_hour', 'id' => 'freepick-hour', 'label' => 'Time', 'bag' => 'freepick', 'choices' => $hours, 'value' => $savedHour, 'disabled' => !$failedPick || old('mode') !== 'later'])
                    </div>
                </div>
                <p class="small text-danger mb-2" data-freepick-date-error role="alert" hidden></p>
                @include('components.forms.error', ['field' => 'scheduled_at', 'bag' => 'freepick'])
                <p id="freepick-timezone" class="small text-muted mb-4">Times are in {{ config('app.timezone') }}.</p>
            </div>
            <p class="small text-muted mb-4">This selection will replace the current Freepick at the chosen time.</p>
            <div class="mb-4">
                <input type="hidden" name="send_email" value="0">
                @include('components.forms.checkbox', ['id' => 'freepick-send-email', 'name' => 'send_email', 'value' => '1', 'label' => 'Automatically send the Freepick email', 'checked' => !$failedPick || old('send_email', '0') == '1'])
                <p class="small text-muted mt-2 mb-0">Sent to all Free Pick subscribers after the piece becomes Freepick and Discover has been refreshed. Uncheck to send it manually later.</p>
                @include('components.forms.error', ['field' => 'send_email', 'bag' => 'freepick'])
            </div>
            <div class="d-flex flex-wrap justify-content-end gap-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-dark" data-freepick-submit>@icon('calendar-days')<span data-freepick-submit-label>Make Freepick now</span></button>
            </div>
        </form>
    @endslot
@endcomponent
