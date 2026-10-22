@if(($variant ?? null) === 'cards')
    @php
        $promotions = [
            'crashcourse' => ['category' => 'Crash courses', 'color' => 'mint', 'action' => 'Start learning', 'description' => 'A fresh perspective, one lesson at a time. Delivered to your inbox.'],
            'ebook' => ['category' => 'eBooks', 'color' => 'cream', 'action' => 'Explore eBook', 'description' => 'A little reading to take your piano journey further.'],
            'escore' => ['category' => 'eScores', 'color' => 'lavender', 'action' => 'Discover eScore', 'description' => 'Find your next piece to bring to the piano.'],
        ];
    @endphp
    @foreach($ads as $type)
        @if(isset($promotions[$type]) && ! empty($ad[$type]))
            @php($promoDescription = $type === 'crashcourse' ? $ad[$type]->description : $ad[$type]->subtitle)
            @include('components.promo-card', array_merge($promotions[$type], [
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
	@if(view()->exists('components.display.ads.' . $view) && $ad[$view])
	@include('components.display.ads.' . $view)
	@endif
@endforeach
@endif
