@include('webapp.explore.link', ['href' => \App\Services\WebApp\ExploreCatalogue::url($choice['params'], $choiceLabel), 'label' => 'View all matching pieces', 'description' => $choice['count'].' '.Str::plural('piece', $choice['count']).' to explore', 'icon' => 'list'])
@if($choice['example'])
@php($example = $choice['example'])
<div data-explore-example data-audio="{{ $example->audio }}" data-preview="{{ $example->hasWebMediaAccess(auth('web')->user()) ? 0 : config('webapp.media_preview_seconds') }}">
    <div class="explore-link">
        <button type="button" class="btn-raw d-flex align-items-center gap-3 text-start text-primary explore-copy" data-example-toggle aria-label="Hear an example" aria-pressed="false">
            <span data-example-play>@icon('circle-play', ['mr' => 0, 'size' => 'lg'])</span>
            <span data-example-pause hidden>@icon('circle-pause', ['mr' => 0, 'size' => 'lg'])</span>
            <span class="explore-copy">
                <span data-example-idle>Hear an example</span>
                <span data-example-identity hidden><span class="d-block" data-example-title>{{ $example->short_name }}</span><small class="d-block text-muted">{{ optional($example->composer)->short_name }}</small></span>
            </span>
        </button>
        <a class="btn btn-secondary btn-sm" data-example-go hidden href="{{ route('webapp.pieces.show', $example) }}" aria-label="Go to {{ $example->short_name }}">Go</a>
    </div>
    <p class="small text-muted px-3 mb-2" data-example-status role="status" hidden></p>
</div>
@endif
