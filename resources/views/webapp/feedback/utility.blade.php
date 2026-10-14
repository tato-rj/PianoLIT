<div class="feedback-utility" data-feedback-utility>
    <button type="button" class="btn btn-secondary btn-sm feedback-launcher" data-feedback-launcher data-bs-toggle="modal" data-bs-target="#feedback-modal" aria-controls="feedback-modal">@icon('message-square', ['mr' => 0])<span>Feedback</span></button>
    <div class="feedback-confirmation" data-feedback-confirmation role="status" aria-live="polite" aria-atomic="true" hidden></div>
</div>
@include('webapp.feedback.modal')
