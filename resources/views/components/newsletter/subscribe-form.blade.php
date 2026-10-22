<form method="POST" action="{{ route('newsletter.subscribe') }}" id="{{ $formId ?? 'newsletter-subscription-form' }}">
    @csrf
    @include('components.form.subscription.hidden', ['formId' => $formId ?? 'newsletter-subscription-form'])
    @input([
        'id' => ($formId ?? 'newsletter-subscription-form').'-email', 'name' => 'email', 'type' => 'email',
        'label' => 'Email address', 'placeholder' => 'you@example.com', 'autocomplete' => 'email',
        'describedBy' => ($formId ?? 'newsletter-subscription-form').'-note',
    ])
    <button type="submit" class="btn btn-primary w-100 mt-2 mb-3" disable-on-submit>Keep me in the loop</button>
    <p class="small text-muted text-center mb-0" id="{{ $formId ?? 'newsletter-subscription-form' }}-note">Once a month, only when there’s something new.<br>Unsubscribe anytime.</p>
</form>
