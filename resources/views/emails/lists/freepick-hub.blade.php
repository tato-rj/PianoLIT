@php
    $hubs = [
        'games' => ['title' => 'Have fun with music theory', 'description' => 'Try Note Match, which listens to the notes you play.', 'button' => 'Explore the games', 'route' => 'games.home', 'image' => 'hub-games.jpg'],
        'shop' => ['title' => 'Explore PianoLIT Shop', 'description' => 'Browse piano-themed T-shirts, stickers, eBooks and sheet music.', 'button' => 'Browse the shop', 'route' => 'shop.home', 'image' => 'hub-shop.jpg'],
        'metronome' => ['title' => 'A metronome with your voice', 'description' => 'Record your count and try robot or chipmunk effects.', 'button' => 'Try the metronome', 'route' => 'metronome.home', 'image' => 'hub-metronome.jpg'],
    ];
    // Workers still using the previous mail class need the same fallback in both formats.
    $hub = $hubs[$featuredHub ?? 'games'] ?? $hubs['games'];
@endphp
@component('mail::freepick.hub', ['title' => $hub['title'], 'url' => route($hub['route']), 'button' => $hub['button'], 'image' => $websiteUrl.mix('images/emails/welcome/'.$hub['image'])])
{{ $hub['description'] }}
@endcomponent
