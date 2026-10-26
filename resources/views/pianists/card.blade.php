@php
    $nationality = optional($pianist->country)->nationality ?: optional($pianist->country)->name;
    $description = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $pianist->biography)));
@endphp
@component('components.cards.resource', [
    'id' => $cardId ?? 'pianist-'.$pianist->id, 'title' => rtrim($pianist->name, '. '),
    'format' => $nationality ?: 'Meet the artist',
    'description' => $description ? Str::limit($description, 140) : 'Explore the pianist’s life and recordings.',
    'url' => route('resources.pianists.show', $pianist->slug), 'action' => 'Meet the pianist',
    'artworkClass' => 'resource-card__art--portrait',
])
    @slot('artwork')
        @if($pianist->cover_path)
            <img src="{{ storage($pianist->cover_path) }}" alt="" loading="lazy" decoding="async">
        @else
            @include('learn.components.artwork', ['kind' => 'pianists'])
        @endif
    @endslot
@endcomponent
