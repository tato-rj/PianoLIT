@if(($variant ?? null) === 'cards')
    @php
        $promotions = [
            'crashcourse' => ['category' => 'Crash courses', 'color' => 'mint', 'action' => 'Start learning', 'description' => 'Short music lessons delivered by email.'],
            'ebook' => ['category' => 'eBooks', 'color' => 'cream', 'action' => 'Explore eBook', 'description' => 'Read about piano music, theory, and practice.'],
            'escore' => ['category' => 'eScores', 'color' => 'lavender', 'action' => 'Discover eScore', 'description' => 'Browse piano sheet music in digital editions.'],
        ];
    @endphp
    @foreach($ads as $type)
        @if(isset($promotions[$type]) && ! empty($ad[$type]))
            @php($promoDescription = $type === 'crashcourse' ? $ad[$type]->description : $ad[$type]->subtitle)
            @include('components.cards.promotion', array_merge($promotions[$type], [
                'title' => $ad[$type]->title,
                'url' => $ad[$type]->showRoute(),
                'image' => $type === 'crashcourse' ? $ad[$type]->cover_image() : ($ad[$type]->mockup_image() ?: $ad[$type]->cover_image()),
                'imageFit' => $type === 'crashcourse' ? 'cover' : 'contain',
                'description' => $promoDescription ? \Illuminate\Support\Str::limit(strip_tags($promoDescription), 130) : $promotions[$type]['description'],
            ]))
        @endif
    @endforeach
@else
@php(shuffle($ads))

@foreach($ads as $view)
	@if(view()->exists('components.promotions.ads.' . $view) && $ad[$view])
	@include('components.promotions.ads.' . $view)
	@endif
@endforeach
@endif
