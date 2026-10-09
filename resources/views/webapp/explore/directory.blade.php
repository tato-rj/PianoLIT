<details class="explore-section" open>
    <summary>@icon('layers', ['mr' => 0, 'size' => 'lg'])<span>Level</span>@icon('chevron-right', ['mr' => 0, 'classes' => 'explore-chevron'])</summary>
    <div class="explore-branch">
        @forelse($levels as $level)
        <a class="explore-link link-none {{ $selected && $selected->id === $level->id ? 'is-selected' : '' }}" href="{{ route('webapp.explore', ['level' => $level->name]) }}" @if(request('level') === $level->name) aria-current="page" @endif>
            @icon('circle', ['filled' => true, 'classes' => 'color-'.lastword($level->name), 'mr' => 0])
            <span class="explore-copy">{{ ucwords($level->name) }}</span>@icon('chevron-right', ['mr' => 0, 'classes' => 'explore-chevron'])
        </a>
        @empty<p class="text-muted p-3">Levels are being prepared.</p>@endforelse
    </div>
</details>
<details class="explore-section">
    <summary>@icon('music', ['mr' => 0, 'size' => 'lg'])<span>Mood<small class="d-block text-muted fw-normal">Calm, playful, dramatic...</small></span>@icon('chevron-right', ['mr' => 0, 'classes' => 'explore-chevron'])</summary>
    <div class="explore-branch">
        @foreach($moods as $key => $mood)
            @include('webapp.explore.link', ['href' => \App\Services\WebApp\ExploreCatalogue::url(['mood' => $key], $mood['label']), 'label' => $mood['label'], 'description' => $mood['description'], 'icon' => $mood['icon']])
        @endforeach
    </div>
</details>
<details class="explore-section">
    <summary>@icon('hand', ['mr' => 0, 'size' => 'lg'])<span>Technique<small class="d-block text-muted fw-normal">Hands, patterns & more</small></span>@icon('chevron-right', ['mr' => 0, 'classes' => 'explore-chevron'])</summary>
    <div class="explore-branch">
        @forelse($tags->where('type', 'technique') as $tag)
            @include('webapp.explore.link', ['href' => \App\Services\WebApp\ExploreCatalogue::url(['tag' => $tag->id], ucfirst($tag->name)), 'label' => ucfirst($tag->name)])
        @empty<p class="text-muted p-3">Technique collections are being prepared.</p>@endforelse
    </div>
</details>
<details class="explore-section">
    <summary>@icon('user', ['mr' => 0, 'size' => 'lg'])<span>Composers</span>@icon('chevron-right', ['mr' => 0, 'classes' => 'explore-chevron'])</summary>
    <div class="explore-branch">
        <a class="explore-link link-none" href="{{ route('webapp.composers.index', ['sort' => 'name']) }}">
            <span class="explore-portraits">@foreach($portraits as $composer)<img src="{{ $composer->cover_image }}" alt="" class="rounded-circle" loading="lazy">@endforeach</span>
            <span class="explore-copy">All composers<small class="d-block text-muted">Browse A–Z</small></span>@icon('chevron-right', ['mr' => 0])
        </a>
        @include('webapp.explore.link', ['href' => route('webapp.composers.index', ['gender' => 'female', 'sort' => 'name']), 'label' => 'Women composers', 'icon' => 'venus'])
        <details class="explore-countries">
            <summary>@icon('globe', ['mr' => 0])<span>By country</span>@icon('chevron-right', ['mr' => 0, 'classes' => 'explore-chevron'])</summary>
            @foreach($countries as $country)
                @include('webapp.explore.link', ['href' => route('webapp.composers.index', ['country' => $country->id, 'sort' => 'name']), 'label' => $country->name])
            @endforeach
        </details>
    </div>
</details>
<details class="explore-section">
    <summary>@icon('layers', ['mr' => 0, 'size' => 'lg'])<span>Periods & Styles</span>@icon('chevron-right', ['mr' => 0, 'classes' => 'explore-chevron'])</summary>
    <div class="explore-branch explore-styles">
        @forelse($tags->whereIn('type', ['period', 'genre']) as $tag)
            @include('webapp.explore.link', ['href' => \App\Services\WebApp\ExploreCatalogue::url(['tag' => $tag->id], ucfirst($tag->name)), 'label' => ucfirst($tag->name)])
        @empty<p class="text-muted p-3">Styles are being prepared.</p>@endforelse
    </div>
</details>
@include('webapp.explore.link', ['href' => \App\Services\WebApp\ExploreCatalogue::url(['past' => 1], 'Past free picks'), 'label' => 'Past free picks', 'icon' => 'clock', 'classes' => 'explore-past fw-bold'])
