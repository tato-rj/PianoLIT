<section class="signup-card card rounded-sm shadow" aria-labelledby="{{ $headingId }}" @if($preview ?? false) data-ds-preview @endif>
    @include('components.access-prompt-icon')
    @include('components.access-prompt-body', ['descriptionId' => $headingId.'-description'])
</section>
