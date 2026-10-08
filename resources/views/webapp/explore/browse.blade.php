@component('components.modal', ['id' => 'explore-styles'])
    @slot('header') Periods & styles @endslot
    @slot('body')
        <div class="explore-browse-links d-flex flex-wrap gap-2">
            @foreach($styleNames as $tagName)
                @include('webapp.explore.tag-link', ['kind' => 'simple', 'tagName' => $tagName])
            @endforeach
        </div>
    @endslot
@endcomponent

@component('components.modal', ['id' => 'explore-browse'])
    @slot('header') Browse repertoire @endslot
    @slot('body')
        @foreach($browseTags as $type => $group)
        <div class="mb-4">
            <h6>{{ ucfirst($type) }}</h6>
            <div class="explore-browse-links d-flex flex-wrap gap-2">
                @foreach($group as $tag)
                    @include('webapp.explore.tag-link', ['kind' => 'simple', 'tagName' => $tag->name])
                @endforeach
            </div>
        </div>
        @endforeach
    @endslot
@endcomponent

@component('components.modal', ['id' => 'explore-countries'])
    @slot('header') Composers by country @endslot
    @slot('body')
        <div class="list-group list-group-flush">
            @forelse($countries as $country)
            <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="{{ route('webapp.composers.index', ['search' => $country->name]) }}">
                {{ $country->name }} @icon('arrow-right', ['mr' => 0])
            </a>
            @empty
            <p class="text-muted">No countries to browse yet.</p>
            @endforelse
        </div>
    @endslot
@endcomponent
