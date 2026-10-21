@php($artwork = 'images/home/projects/'.$project['key'].'.svg')
<a class="project-card pastel-card" data-project="{{ $project['key'] }}" data-pastel-color="{{ $project['color'] }}" href="{{ $project['url'] }}" @if($preview ?? false) data-ds-preview @endif
   aria-labelledby="project-{{ $project['key'] }}-title" aria-describedby="project-{{ $project['key'] }}-description">
    <div class="project-card__heading">
        <h2 id="project-{{ $project['key'] }}-title">{{ $project['title'] }}</h2>
        <span class="project-card__arrow" aria-hidden="true">↗</span>
    </div>
    <img class="project-card__art" src="{{ asset(mix($artwork)) }}" width="320" height="280" alt="" draggable="false">
    <div class="project-card__caption">
        <p id="project-{{ $project['key'] }}-description">{{ $project['description'] }}</p>
        <span>{{ $project['destination'] }}</span>
    </div>
</a>
