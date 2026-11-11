@php
    $hubs = [
        'games' => ['title' => 'Games', 'description' => 'Music theory games.', 'button' => 'Play games', 'route' => 'games.home', 'image' => 'hub-games.jpg'],
        'shop' => ['title' => 'Shop', 'description' => 'eBooks, scores & merchandise.', 'button' => 'Browse the shop', 'route' => 'shop.home', 'image' => 'hub-shop.jpg'],
        'metronome' => ['title' => 'Metronome', 'description' => 'Set a tempo and practice.', 'button' => 'Open metronome', 'route' => 'metronome.home', 'image' => 'hub-metronome.jpg'],
    ];
    // Workers still using the previous mail class need the same fallback in both formats.
    $hub = $hubs[$featuredHub ?? 'games'] ?? $hubs['games'];
@endphp
@component('mail::freepick.hub', ['title' => $hub['title'], 'url' => route($hub['route']), 'button' => $hub['button'], 'image' => $websiteUrl.mix('images/emails/welcome/'.$hub['image'])])
{{ $hub['description'] }}
@endcomponent
