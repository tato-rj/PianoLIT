@foreach([
    ['category' => 'Shop', 'title' => 'PianoLIT Shop', 'description' => 'Browse merchandise, eBooks and digital sheet music.', 'action' => 'Visit Shop', 'url' => route('shop.home'), 'color' => 'cream'],
    ['category' => 'Games', 'title' => 'PianoLIT Games', 'description' => 'Play music games and test your knowledge.', 'action' => 'Visit Games', 'url' => route('games.home'), 'color' => 'lavender'],
    ['category' => 'Learn', 'title' => 'PianoLIT Learn', 'description' => 'Find music tools, courses, infographics and quizzes.', 'action' => 'Visit Learn', 'url' => route('learn.home'), 'color' => 'mint'],
] as $hub)
    @include('components.cards.promotion', $hub)
@endforeach
