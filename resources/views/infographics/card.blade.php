@component('components.resource-card', [
    'id' => 'infographic-'.$item->id,
    'url' => route('resources.infographs.show', $item),
    'title' => rtrim($item->name, '. '),
    'description' => $item->description,
    'format' => 'Free visual guide',
    'action' => 'View & download',
    'artworkClass' => 'resource-card__art--poster',
])
    @slot('artwork')
        <img src="{{ storage($item->thumbnail_path) }}" alt="" loading="lazy" decoding="async">
    @endslot
@endcomponent
