@php
    $compact = $compact ?? false;
    $artwork = 'images/home/worlds/'.$project['key'].'.webp';
    $landscapeArtwork = in_array($project['key'], ['learn', 'metronome', 'blog']);
    $icons = ['webapp' => 'pianolit', 'shop' => 'shopping-basket', 'games' => 'trophy', 'learn' => 'graduation-cap', 'metronome' => 'metronome', 'blog' => 'newspaper'];
    $labels = ['webapp' => 'Discover', 'ios' => 'Take it with you', 'shop' => 'For your piano', 'games' => 'Play', 'learn' => 'Go deeper', 'metronome' => 'Find your rhythm', 'blog' => 'Get inspired'];
    $actions = ['webapp' => 'Explore pieces', 'ios' => 'Meet the app', 'shop' => 'Visit the shop', 'games' => "Let's play", 'learn' => 'Start learning', 'metronome' => 'Open metronome', 'blog' => 'Read the blog'];
@endphp
<a class="project-card card-lift {{ $compact ? 'project-card--compact' : '' }}" data-project="{{ $project['key'] }}" href="{{ $project['url'] }}"
   @if($preview ?? false) data-ds-preview @endif
   @if($project['key'] === 'ios') target="_blank" rel="noopener noreferrer" @endif
   aria-labelledby="{{ $cardPrefix ?? 'project' }}-{{ $project['key'] }}-title" aria-describedby="{{ $cardPrefix ?? 'project' }}-{{ $project['key'] }}-description">
    <div class="project-card__copy">
        @pill(['label' => $labels[$project['key']] ?? 'Explore', 'color' => 'white', 'text' => 'dark', 'classes' => 'project-card__tag text-uppercase mb-2'])
        <h2 class="text-body d-flex align-items-center gap-2" id="{{ $cardPrefix ?? 'project' }}-{{ $project['key'] }}-title">
            <span class="d-inline-flex align-items-center justify-content-center flex-shrink-0 fs-5" aria-hidden="true">
                @if($project['key'] === 'ios')
                    @fa(['icon' => 'apple', 'fa_type' => 'b', 'mr' => 0, 'title' => ''])
                @else
                    @icon($icons[$project['key']], ['mr' => 0])
                @endif
            </span>
            <span>{{ $project['title'] }}</span>
        </h2>
        <p id="{{ $cardPrefix ?? 'project' }}-{{ $project['key'] }}-description">{{ $project['description'] }}</p>
    </div>
    <div class="project-card__media">
        <img src="{{ asset(mix($artwork)) }}" width="{{ $landscapeArtwork ? 960 : 720 }}" height="{{ $landscapeArtwork ? 640 : 900 }}" alt="" @if($compact) loading="lazy" @endif>
    </div>
    <span class="project-card__action btn btn-secondary btn-sm {{ $compact ? 'rounded-circle project-card__action--compact' : 'rounded-pill' }}" aria-hidden="true">
        <span class="project-card__action-label">{{ $actions[$project['key']] ?? 'Explore' }}</span>
        @icon($compact ? 'arrow-right' : 'arrow-up-right', ['mr' => 0, 'ml' => $compact ? 0 : 2])
    </span>
    <span class="visually-hidden">{{ $project['destination'] ?? '' }}</span>
</a>
