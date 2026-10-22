@php($artwork = 'images/home/editorial/'.$project['key'].'.webp')
<a class="project-card card-lift border" data-project="{{ $project['key'] }}" href="{{ $project['url'] }}"
   @if($preview ?? false) data-ds-preview @endif
   @if($project['key'] === 'ios') target="_blank" rel="noopener noreferrer" @endif
   aria-labelledby="{{ $cardPrefix ?? 'project' }}-{{ $project['key'] }}-title" aria-describedby="{{ $cardPrefix ?? 'project' }}-{{ $project['key'] }}-description">
    <img class="project-card__art" src="{{ asset(mix($artwork)) }}" width="720" height="960" alt="" draggable="false">
    <div class="project-card__heading">
        <h2 class="text-body" id="{{ $cardPrefix ?? 'project' }}-{{ $project['key'] }}-title">@if($project['key'] === 'ios')@fa(['icon' => 'apple', 'fa_type' => 'b', 'mr' => 1])@endif{{ $project['title'] }}</h2>
    </div>
    <p class="project-card__caption" id="{{ $cardPrefix ?? 'project' }}-{{ $project['key'] }}-description">{{ $project['description'] }}</p>
    @icon('arrow-up-right', ['mr' => 3, 'classes' => 'position-absolute top-0 end-0 mt-3 text-muted icon-size-lg'])
    <span class="visually-hidden">{{ $project['destination'] ?? '' }}</span>
</a>
