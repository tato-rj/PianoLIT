{{-- AJAX feedback uses the same server-rendered alert as controller responses.
     Insert message text with textContent; never interpolate a response into HTML. --}}
<div data-feedback-output></div>
@foreach(['success' => ['green', 'Success'], 'error' => ['red', 'Sorry']] as $type => [$color, $label])
    <template data-feedback-template="{{ $type }}">
        @alert(['color' => $color, 'message' => '<strong class="me-2">'.$label.' | </strong><span data-feedback-message></span>', 'dismissible' => true, 'floating' => 'top'])
    </template>
@endforeach
