@component('mail::welcome.hubs', ['title' => 'A world to explore', 'introduction' => 'There’s more to PianoLIT than finding your next piece. Follow your curiosity.', 'url' => $websiteUrl, 'button' => 'Explore PianoLIT.com'])
@component('mail::welcome.hub', ['title' => 'Learn', 'image' => $websiteUrl.mix('images/emails/welcome/hub-learn.jpg'), 'alt' => 'PianoLIT Circle of Fifths guide'])
Explore theory tools, courses and quizzes that make music click.
@endcomponent

@component('mail::welcome.hub', ['title' => 'Blog', 'image' => $websiteUrl.mix('images/emails/welcome/hub-blog.jpg'), 'alt' => 'PianoLIT journal with a story about listening'])
Find practice tips, musical stories and fresh inspiration on the blog.
@endcomponent

@component('mail::welcome.hub', ['title' => 'Metronome', 'image' => $websiteUrl.mix('images/emails/welcome/hub-metronome.jpg'), 'alt' => 'A wooden PianoLIT metronome'])
Find your rhythm with a simple, flexible metronome. Even count along in your own voice.
@endcomponent

@component('mail::welcome.hub', ['title' => 'Shop', 'status' => 'New hub coming soon', 'image' => $websiteUrl.mix('images/emails/welcome/hub-shop.jpg'), 'alt' => 'PianoLIT piano scores and collection artwork'])
eBooks, eScores and more for your piano, with a new home on the way.
@endcomponent

@component('mail::welcome.hub', ['title' => 'Games', 'status' => 'Coming soon', 'image' => $websiteUrl.mix('images/emails/welcome/hub-games.jpg'), 'alt' => 'A preview of PianoLIT music games on a tablet'])
A playful way to grow your musical skills. New games are on the way.
@endcomponent
@endcomponent
