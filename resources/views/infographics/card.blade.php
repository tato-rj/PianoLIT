@component('components.cards.resource', [
    'id' => 'infographic-'.$item->id,
    'url' => route('resources.infographs.show', $item),
    'title' => rtrim($item->name, '. '),
    'description' => \App\Services\Learn\InfographicGuide::description($item),
    'format' => 'Free visual guide',
    'action' => 'View & download',
    'variant' => 'poster',
    'artworkClass' => 'resource-card__art--poster',
])
    @slot('artwork')
        <img src="{{ storage($item->thumbnail_path) }}" alt="" loading="lazy" decoding="async">
    @endslot
@endcomponent
