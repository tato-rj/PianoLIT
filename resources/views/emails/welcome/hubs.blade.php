@component('mail::welcome.hubs', ['title' => 'PianoLIT resources', 'introduction' => 'Use PianoLIT’s learning resources, practice tools, shop, and games.', 'url' => $websiteUrl, 'button' => 'Explore PianoLIT.com'])
@component('mail::welcome.hub', ['title' => 'Learn', 'image' => $websiteUrl.mix('images/emails/welcome/hub-learn.jpg'), 'alt' => 'PianoLIT Circle of Fifths guide'])
Study music theory with tools, courses, and quizzes.
@endcomponent

@component('mail::welcome.hub', ['title' => 'Blog', 'image' => $websiteUrl.mix('images/emails/welcome/hub-blog.jpg'), 'alt' => 'PianoLIT journal with a story about listening'])
Read articles about piano music, practice, and music history.
@endcomponent

@component('mail::welcome.hub', ['title' => 'Metronome', 'image' => $websiteUrl.mix('images/emails/welcome/hub-metronome.jpg'), 'alt' => 'A wooden PianoLIT metronome'])
Practice with a metronome and record your own counting voice.
@endcomponent

@component('mail::welcome.hub', ['title' => 'Shop', 'image' => $websiteUrl.mix('images/emails/welcome/hub-shop.jpg'), 'alt' => 'PianoLIT piano scores and collection artwork'])
Browse T-shirts, stickers, eBooks, and eScores.
@endcomponent

@component('mail::welcome.hub', ['title' => 'Games', 'image' => $websiteUrl.mix('images/emails/welcome/hub-games.jpg'), 'alt' => 'A preview of PianoLIT music games on a tablet'])
Practice music theory with interactive games.
@endcomponent
@endcomponent
