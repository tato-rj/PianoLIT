<section class="account-library-empty text-center mx-auto py-4 py-md-5" aria-labelledby="{{ $kind }}-empty-title">
    @include('users.components.illustrations.'.$kind)
    <h2 id="{{ $kind }}-empty-title" class="h4 mt-4 mb-2">{{ $heading }}</h2>
    <p class="text-muted mb-4">{{ $description }}</p>
    <div class="d-flex flex-wrap justify-content-center gap-3">{{ $slot }}</div>
</section>
