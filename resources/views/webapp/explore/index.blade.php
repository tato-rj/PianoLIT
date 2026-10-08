@extends('webapp.layouts.app', ['title' => 'Explore'])

@section('content')
@include('webapp.layouts.header', ['title' => 'Explore', 'subtitle' => 'Follow your curiosity. Find something new to play.'])

<div id="explore-page">
    <section class="explore-search mb-5" aria-label="Search repertoire">
        @include('webapp.search.form', ['exploreSearch' => true, 'searchPlaceholder' => 'Search pieces, composers...'])
    </section>

    @component('webapp.explore.rows.row', ['data' => ['label' => 'Periods & styles']])
        @slot('action')
            <button type="button" class="btn-raw link-primary" data-bs-toggle="modal" data-bs-target="#explore-styles">View all @icon('arrow-right', ['ml' => 1, 'mr' => 0])</button>
        @endslot
        <div class="explore-periods">
            @foreach($featuredPeriods as $tag)
                @include('webapp.explore.tag-link', ['kind' => 'period', 'tagName' => $tag->name])
            @endforeach
        </div>
        <div class="explore-other-periods mt-3">
            @foreach($otherPeriods as $tagName)
                @include('webapp.explore.tag-link', ['kind' => 'simple', 'tagName' => $tagName])
            @endforeach
        </div>
    @endcomponent

    @if($moods->isNotEmpty())
    @component('webapp.explore.rows.row', ['data' => ['label' => 'Explore a mood']])
        <div class="explore-moods">
            @foreach($moods as $tagName)
                @include('webapp.explore.tag-link', ['kind' => 'mood', 'tagName' => $tagName])
            @endforeach
        </div>
    @endcomponent
    @endif

    @component('webapp.explore.rows.row', ['data' => ['label' => 'Browse by level']])
        <div class="explore-levels">
            @foreach($levels as $tag)
                @include('webapp.explore.tag-link', ['kind' => 'level', 'tagName' => $tag->name])
            @endforeach
        </div>
    @endcomponent

    @component('webapp.explore.rows.row', ['data' => ['label' => 'Explore composers']])
        <div class="explore-composers border rounded overflow-hidden">
            <a href="{{ route('webapp.composers.index') }}" class="explore-composers-intro link-none d-flex align-items-center gap-4 p-3">
                @if($composers->isNotEmpty())
                <span class="explore-portraits d-flex flex-shrink-0">
                    @foreach($composers as $composer)
                    <img src="{{ $composer->cover_image }}" alt="" class="rounded-circle" loading="lazy" width="88" height="88">
                    @endforeach
                </span>
                @endif
                <span>
                    <span class="h5 d-block mb-2">Meet someone new</span>
                    <span class="text-muted">Explore the people behind the music.</span>
                </span>
            </a>
            <div class="explore-composer-links">
                <a href="{{ route('webapp.composers.index') }}">All composers @icon('arrow-right', ['mr' => 0])</a>
                <a href="{{ route('webapp.search.results', ['search' => 'women composers']) }}">Women composers @icon('arrow-right', ['mr' => 0])</a>
                <button type="button" class="btn-raw text-primary" data-bs-toggle="modal" data-bs-target="#explore-countries">By country @icon('arrow-right', ['mr' => 0])</button>
            </div>
        </div>
    @endcomponent

    @component('webapp.explore.rows.row', ['data' => ['label' => 'Recent free picks'], 'link' => ['url' => route('webapp.highlights'), 'label' => 'View all']])
        <div class="explore-free-picks">
            @forelse($freePicks as $piece)
                @include('webapp.discover.cards.compact-piece', ['pieceTitle' => $piece->medium_name])
            @empty
                <p class="text-muted">New picks are on their way. Explore a style or mood above.</p>
            @endforelse
        </div>
    @endcomponent
</div>

@include('webapp.explore.browse')
@endsection

@push('scripts')
<script src="{{ mix('js/views/explore.js') }}"></script>
@endpush
