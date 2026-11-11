@php
    $hubs = [
        'games' => ['title' => 'Your piano is the controller', 'description' => 'Note Match listens to the notes you play.', 'button' => 'Explore the games', 'route' => 'games.home', 'image' => 'hub-games.jpg'],
        'shop' => ['title' => 'Wear a little PianoLIT', 'description' => 'Piano-themed T-shirts, stickers & more.', 'button' => 'See the collection', 'route' => 'shop.home', 'image' => 'hub-shop.jpg'],
        'metronome' => ['title' => 'A metronome with your voice', 'description' => 'Your own count, with robot and chipmunk effects.', 'button' => 'Try it out', 'route' => 'metronome.home', 'image' => 'hub-metronome.jpg'],
    ];
    // Workers still using the previous mail class need the same fallback in both formats.
    $hub = $hubs[$featuredHub ?? 'games'] ?? $hubs['games'];
@endphp
@component('mail::freepick.hub', ['title' => $hub['title'], 'url' => route($hub['route']), 'button' => $hub['button'], 'image' => $websiteUrl.mix('images/emails/welcome/'.$hub['image'])])
{{ $hub['description'] }}
@endcomponent
