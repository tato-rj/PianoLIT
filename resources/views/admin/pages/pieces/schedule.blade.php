@php
    $failedPick = $failedFreePick ?? null;
@endphp
@component('components.modal', ['id' => 'freepick-schedule-modal', 'header' => 'Schedule Freepick', 'centered' => true])
    @slot('body')
        <form method="POST" data-freepick-form
              @if($failedPick) action="{{ route('admin.pieces.schedule', ['piece' => $failedPick] + $returnFilters) }}" @endif
              data-restore="{{ $failedPick ? 'true' : 'false' }}">
            @csrf
            @if($errors->getBag('freepick')->any())
                <div class="alert alert-red" role="alert" data-freepick-error>{{ $errors->getBag('freepick')->first() }}</div>
            @endif
            <p class="font-weight-bold mb-4" data-freepick-title>{{ $failedPick ? $failedPick->long_name : '' }}</p>
            <fieldset class="mb-4">
                <legend class="small text-muted mb-2">When should this piece become the Freepick?</legend>
                @include('components.form.checkbox', ['type' => 'radio', 'id' => 'freepick-now', 'name' => 'mode', 'value' => 'now', 'label' => 'Make it Freepick immediately', 'checked' => !$failedPick || old('mode') !== 'later'])
                @include('components.form.checkbox', ['type' => 'radio', 'id' => 'freepick-later', 'name' => 'mode', 'value' => 'later', 'label' => 'Schedule for later', 'checked' => $failedPick && old('mode') === 'later'])
                @include('components.form.error', ['field' => 'mode', 'bag' => 'freepick'])
            </fieldset>
            <div data-freepick-calendar @if(!$failedPick || old('mode') !== 'later') hidden @endif>
                @input(['name' => 'scheduled_at', 'id' => 'freepick-date', 'type' => 'datetime-local', 'label' => 'Date and time', 'bag' => 'freepick', 'required' => '', 'describedBy' => 'freepick-timezone'])
                <p id="freepick-timezone" class="small text-muted mb-4">Times are in {{ config('app.timezone') }}.</p>
            </div>
            <p class="small text-muted mb-4">This selection will replace the current Freepick at the chosen time.</p>
            <div class="d-flex flex-wrap justify-content-end gap-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-dark" data-freepick-submit>@icon('calendar-days')<span data-freepick-submit-label>Make Freepick now</span></button>
            </div>
        </form>
    @endslot
@endcomponent
