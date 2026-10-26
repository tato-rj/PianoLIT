@component('components.dialogs.fullscreen.panel', ['id' => 'feedback-modal', 'headingId' => 'feedback-heading', 'classes' => 'feedback-modal'])
    @component('components.dialogs.fullscreen.header', ['headingId' => 'feedback-heading', 'closeLabel' => 'Close feedback'])
        @slot('headerContent')<span></span>@endslot
    @endcomponent
    <form class="feedback-form" action="{{ route('webapp.feedback.store') }}" method="POST" data-feedback-form>
        @csrf
        <input type="hidden" name="rating" value="good">
        <input type="hidden" name="page_context" value="{{ app(\App\Services\FeedbackContext::class)->forPage(request()) }}">
        <div class="feedback-stage" data-feedback-swipe>
            <h1 id="feedback-heading" class="feedback-question">How was your PianoLIT<br>experience?</h1>
            <div class="feedback-expression">@include('webapp.feedback.face')</div>
            <div class="feedback-rating-window" aria-hidden="true"><div class="feedback-rating-track" data-feedback-labels><span>BAD</span><span>NOT BAD</span><span>GOOD</span></div></div>
        </div>
        <div class="feedback-picker">
            <div class="feedback-slider">
                <div class="feedback-slider-line" aria-hidden="true"><span></span><span></span><span></span></div>
                <span class="feedback-slider-thumb" data-feedback-thumb aria-hidden="true"><svg class="feedback-handle-mouth" viewBox="0 0 24 24" fill="none"><path data-feedback-handle-mouth d="M 8 11 Q 12 17 16 11" stroke="currentColor" stroke-width="3" stroke-linecap="round" /></svg></span>
                <input type="range" min="0" max="2" step="0.001" value="2" aria-label="Your rating" aria-valuetext="Good" data-feedback-range>
            </div>
            <div class="feedback-picker-labels">
                <button type="button" data-feedback-rating="0">Bad</button>
                <button type="button" data-feedback-rating="1">Not bad</button>
                <button type="button" data-feedback-rating="2">Good</button>
            </div>
        </div>
        <div class="feedback-bottom">
            <div class="feedback-note" data-feedback-note hidden>
                <label class="visually-hidden" for="feedback-message">Message (optional)</label>
                <textarea id="feedback-message" name="message" maxlength="2000" placeholder="Add note" rows="3"></textarea>
                <button type="button" class="feedback-note-back" data-feedback-note-back aria-label="Back to rating">@icon('arrow-left', ['mr' => 0])</button>
            </div>
            <div class="feedback-actions">
                <button type="button" class="feedback-add-note" data-feedback-add-note>Add note</button>
                <button type="submit" class="feedback-submit"><span data-feedback-submit-label>Submit</span>@icon('arrow-right', ['mr' => 0])</button>
            </div>
            <p class="feedback-error" data-feedback-error role="alert" hidden></p>
        </div>
    </form>
@endcomponent

@push('scripts')
<script src="{{ mix('js/views/feedback.js') }}"></script>
@endpush
