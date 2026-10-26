@php
    $courseFormId = $formId ?? 'crashcourse-form';
    $coursePreview = $preview ?? false;
@endphp
<section class="{{ ($framed ?? true) ? 'bg-white border rounded-sm p-4' : '' }}" aria-labelledby="{{ $courseFormId }}-title" @if($coursePreview) data-ds-preview @endif>
    <h2 id="{{ $courseFormId }}-title" class="h5 mb-2">Start this free course</h2>
    <p class="text-muted small mb-4">Enter your name and email. Your first lesson arrives after you sign up, followed by one lesson each day.</p>
    <{{ $coursePreview ? 'div' : 'form' }} id="{{ $courseFormId }}" class="cc-form" @unless($coursePreview) method="POST" action="{{ route('crashcourses.signup', $crashcourse) }}" disable-on-submit @endunless>
        @unless($coursePreview)
            @csrf
            @include('components.form.subscription.hidden', ['formId' => $courseFormId])
        @endunless
        @input([
            'bag' => 'crashcourse', 'id' => $courseFormId.'-first-name', 'name' => 'first_name',
            'label' => 'First name', 'placeholder' => 'Your first name', 'autocomplete' => 'given-name', 'limit' => 120,
            'value' => $coursePreview ? '' : (auth()->user()->first_name ?? ''),
        ])
        @input([
            'bag' => 'crashcourse', 'id' => $courseFormId.'-email', 'name' => 'email', 'type' => 'email',
            'label' => 'Email address', 'placeholder' => 'you@example.com', 'autocomplete' => 'email', 'limit' => 120,
            'value' => $coursePreview ? '' : (auth()->user()->email ?? ''), 'describedBy' => $courseFormId.'-note',
        ])
        @unless($coursePreview)
            @include('auth.fields.recaptcha')
        @endunless
        <button type="{{ $coursePreview ? 'button' : 'submit' }}" class="btn btn-primary w-100 mt-2">Start the course @icon('arrow-right', ['mr' => 0, 'ml' => 2])</button>
        <p class="small text-muted mt-3 mb-0" id="{{ $courseFormId }}-note">Lessons arrive by email. <a href="mailto:{{ config('app.emails.general') }}">Contact us</a> if you’d like to stop receiving them.</p>
    </{{ $coursePreview ? 'div' : 'form' }}>
</section>
