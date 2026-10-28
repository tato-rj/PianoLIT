@component('components.dialogs.modal', ['id' => 'review-modal', 'header' => 'Write a review', 'centered' => true])
@slot('body')
    <p class="text-muted mb-4">{{ $product->title }}</p>
    <form method="POST" action="{{ $product->reviewRoute() }}" data-review-form>
        @csrf
        @guest
        <p class="small text-muted">Sign in to submit your review. Your name below is optional.</p>
        @endguest
        @include('components.forms.rating', ['id' => 'product-review-rating', 'bag' => 'review'])
        @input(['bag' => 'review', 'id' => 'product-review-title', 'name' => 'title', 'label' => 'Review title (optional)', 'placeholder' => 'Sum up your experience', 'limit' => 50, 'required' => false])
        @textarea(['bag' => 'review', 'id' => 'product-review-content', 'name' => 'content', 'label' => 'Your review (optional)', 'placeholder' => 'What did you enjoy? Who would you recommend it to?', 'limit' => 220, 'rows' => 3, 'required' => false, 'textImprover' => false])
        @input(['bag' => 'review', 'id' => 'product-reviewer', 'name' => 'reviewer', 'label' => 'Display name (optional)', 'placeholder' => 'Leave blank to appear as Anonymous', 'limit' => 50, 'required' => false])
        <p class="small text-muted mb-3">Reviews appear after moderation.</p>
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            @auth
            <button type="submit" class="btn btn-primary">Submit review</button>
            @else
            <button type="button" class="btn btn-primary" data-review-signin>Sign in to submit</button>
            @endauth
        </div>
    </form>
@endslot
@endcomponent
