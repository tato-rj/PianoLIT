<details class="explore-section" @if($activeSection === 'level') open @endif>
    <summary class="rounded-sm px-2">@icon('layers', ['mr' => 0, 'size' => 'lg'])<span>Level</span>@icon('chevron-right', ['mr' => 0, 'classes' => 'explore-chevron'])</summary>
    <div class="explore-branch">
        @forelse($levels as $level)
        <a class="explore-link rounded-sm link-none {{ $selected && $selected->id === $level->id ? 'is-selected' : '' }}" href="{{ route('webapp.explore', ['level' => $level->name]) }}" @if(request('level') === $level->name) aria-current="page" @endif>
            @icon('circle', ['filled' => true, 'classes' => 'color-'.lastword($level->name), 'mr' => 0])
            <span class="explore-copy">{{ ucwords($level->name) }}</span>@icon('chevron-right', ['mr' => 0, 'classes' => 'explore-chevron'])
        </a>
        @empty<p class="text-muted p-3">Levels are being prepared.</p>@endforelse
    </div>
</details>
<details class="explore-section" @if($activeSection === 'mood') open @endif>
    <summary class="rounded-sm px-2">@icon('music', ['mr' => 0, 'size' => 'lg'])<span>Mood</span>@icon('chevron-right', ['mr' => 0, 'classes' => 'explore-chevron'])</summary>
    <div class="explore-branch">
        @forelse($moods as $key => $mood)
            @include('webapp.explore.link', ['href' => route('webapp.explore', ['mood' => $key]), 'label' => $mood['label'], 'icon' => $mood['icon'], 'image' => $mood['image'], 'current' => request('mood') === $key, 'classes' => request('mood') === $key ? 'is-selected' : ''])
        @empty<p class="text-muted p-3">Moods are being prepared.</p>@endforelse
    </div>
</details>
<details class="explore-section" @if($activeSection === 'technique') open @endif>
    <summary class="rounded-sm px-2">@icon('hand', ['mr' => 0, 'size' => 'lg'])<span>Technique</span>@icon('chevron-right', ['mr' => 0, 'classes' => 'explore-chevron'])</summary>
    <div class="explore-branch">
        @forelse($tags->where('type', 'technique') as $tag)
            @include('webapp.explore.link', ['href' => route('webapp.explore', ['tag' => $tag->id]), 'label' => ucfirst($tag->name), 'current' => $selectedTag && $selectedTag->id === $tag->id, 'classes' => $selectedTag && $selectedTag->id === $tag->id ? 'is-selected' : ''])
        @empty<p class="text-muted p-3">Technique collections are being prepared.</p>@endforelse
    </div>
</details>
<details class="explore-section" @if($activeSection === 'composers') open @endif>
    <summary class="rounded-sm px-2">@icon('user', ['mr' => 0, 'size' => 'lg'])<span>Composers</span>@icon('chevron-right', ['mr' => 0, 'classes' => 'explore-chevron'])</summary>
    <div class="explore-branch">
        <a class="explore-link rounded-sm link-none flex-column align-items-stretch {{ request('composers') === 'all' ? 'is-selected' : '' }}" href="{{ route('webapp.explore', ['composers' => 'all']) }}" @if(request('composers') === 'all') aria-current="page" @endif>
            {{-- <span class="explore-portraits align-self-center mb-1" aria-hidden="true">@foreach($portraits as $composer)<img src="{{ $composer->cover_image }}" alt="" class="rounded-circle" loading="lazy">@endforeach</span> --}}
            <span class="d-flex align-items-center gap-2">
                <span class="explore-icon">@icon('users', ['mr' => 0, 'size' => 'lg'])</span>
                <span class="explore-copy">All composers</span>
                @icon('chevron-right', ['mr' => 0, 'classes' => 'explore-chevron'])
            </span>
        </a>
        @foreach(\App\Services\WebApp\ComposerGroups::OPTIONS as $group => $option)
            @if($group !== 'all')
                @include('webapp.explore.link', ['href' => route('webapp.explore', ['composers' => $group]), 'label' => $option['label'], 'icon' => $option['icon'], 'current' => request('composers') === $group, 'classes' => request('composers') === $group ? 'is-selected' : ''])
            @endif
        @endforeach
{{--         <details class="explore-countries" @if(request()->filled('country')) open @endif>
            <summary>@icon('globe', ['mr' => 0])<span>By country</span>@icon('chevron-right', ['mr' => 0, 'classes' => 'explore-chevron'])</summary>
            @foreach($countries as $country)
                @include('webapp.explore.link', ['href' => route('webapp.explore', ['country' => $country->id]), 'label' => $country->name, 'current' => (int) request('country') === $country->id, 'classes' => (int) request('country') === $country->id ? 'is-selected' : ''])
            @endforeach
        </details> --}}
    </div>
</details>
<details class="explore-section" @if($activeSection === 'style') open @endif>
    <summary class="rounded-sm px-2">@icon('layers', ['mr' => 0, 'size' => 'lg'])<span>Periods & Styles</span>@icon('chevron-right', ['mr' => 0, 'classes' => 'explore-chevron'])</summary>
    <div class="explore-branch explore-styles">
        @if($tags->where('type', 'period')->isNotEmpty())
            {{-- <p class="explore-group-label text-muted">Periods</p> --}}
            @foreach($tags->where('type', 'period') as $tag)
                <a class="explore-link rounded-sm explore-period link-none {{ $selectedTag && $selectedTag->id === $tag->id ? 'is-selected' : '' }}" href="{{ route('webapp.explore', ['tag' => $tag->id]) }}" @if($selectedTag && $selectedTag->id === $tag->id) aria-current="page" @endif>
                    <img src="{{ $tag->web_cover_image }}" alt="" class="explore-period-image" width="44" height="44" loading="lazy">
                    <span class="explore-copy">{{ ucfirst($tag->name) }}</span>@icon('chevron-right', ['mr' => 0, 'classes' => 'explore-chevron'])
                </a>
            @endforeach
        @endif
        @if($tags->where('type', 'period')->isEmpty())<p class="text-muted p-3">Periods are being prepared.</p>@endif
    </div>
</details>
