@component('components.cards.resource', [
    'id' => 'crashcourse-'.$crashcourse->id,
    'url' => route('crashcourses.show', $crashcourse),
    'title' => rtrim($crashcourse->title, '. '),
    'description' => $crashcourse->description,
    'format' => $crashcourse->lessons_count ? $crashcourse->lessons_count.' '.Str::plural('lesson', $crashcourse->lessons_count).' · By email' : 'Email course',
    'action' => 'Explore the course',
    'artworkClass' => 'position-relative',
])
    @slot('artwork')
        @if($crashcourse->cover_path)
            <img src="{{ $crashcourse->cover_image() }}" alt="" loading="lazy" decoding="async" class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover">
        @else
            @include('learn.components.artwork', ['kind' => 'courses'])
        @endif
    @endslot
@endcomponent
