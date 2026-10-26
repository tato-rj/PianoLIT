@component('components.modal', ['id' => 'modal-subscription', 'cookie' => 'crashcourse15-popup'])
    @slot('header')
        Crash courses
    @endslot
    @slot('body')
        <div class="mb-4">
            <h2 class="h4 mb-2">{{ rtrim($highlightedCrashcourse->title, '. ') }}</h2>
            <p class="text-muted mb-0">{{ $highlightedCrashcourse->description }}</p>
        </div>
        @include('crashcourses.card', ['crashcourse' => $highlightedCrashcourse, 'formId' => 'crashcourse-popup-form', 'framed' => false, 'preview' => false])
    @endslot
@endcomponent
