<div class="offcanvas offcanvas-end" id="options-panel" tabindex="-1" aria-labelledby="options-panel-title">
    @include('components.side-menu-header', ['titleId' => 'options-panel-title', 'title' => 'Options'])
    <div class="offcanvas-body px-2 py-3">
        <div class="list-group">
            <a href="{{route('webapp.composers.show', $piece->composer)}}" class="link-none mb-2 px-3 d-flex d-apart">Meet the composer @icon('chevron-right', ['color' => 'muted', 'mr' => 0, 'ml' => 4])</a>

            @if($piece->siblingsExist())
            <a href="{{route('webapp.pieces.collection', $piece)}}" class="link-none mb-2 px-3 d-flex d-apart">From the same collection @icon('chevron-right', ['color' => 'muted', 'mr' => 0, 'ml' => 4])</a>
            @endif

            <a href="{{route('webapp.pieces.similar', $piece)}}" class="link-none mb-2 px-3 d-flex d-apart">More like this @icon('chevron-right', ['color' => 'muted', 'mr' => 0, 'ml' => 4])</a>

            {{-- <a href="{{route('webapp.pieces.timeline', $piece)}}" class="link-none mb-3 px-3 d-flex d-apart">Timeline @icon('chevron-right', ['color' => 'muted', 'mr' => 0, 'ml' => 4])</a> --}}

            @auth('web')
            <div class="dropdown-divider"></div>

            <div class="py-2 list-group">
                <button type="button" class="btn-raw text-start share-piece link-none mb-2 px-3" data-bs-toggle="modal" data-bs-target="#share-modal">
                    @icon('share') Share this piece
                </button>

                @php($isFavorited = ($piece->webapp_is_favorited ?? $piece->favorites()->where('user_id', auth('web')->id())->whereNotNull('favorite_folder_id')->exists()) > 0)
                <button type="button" class="btn-raw text-start link-none mb-2 px-3 d-block d-md-none" data-favorite-piece="{{ $piece->id }}" data-favorited="{{ $isFavorited ? 'true' : 'false' }}" aria-pressed="{{ $isFavorited ? 'true' : 'false' }}" data-bs-toggle="offcanvas" data-bs-target="#save-to-offcanvas" aria-controls="save-to-offcanvas" data-manage="save-to" data-url="{{route('webapp.pieces.save-to', $piece)}}">
                    @icon('heart', ['color' => 'red', 'filled' => $isFavorited]) Manage favorites
                </button>
            </div>
            @else
            <div class="dropdown-divider"></div>
            <button type="button" class="btn-raw text-start link-none mb-2 px-3 d-block d-md-none" data-bs-toggle="modal" data-bs-target="#signup-favorites-modal">
                @icon('heart', ['color' => 'red']) Save to favorites
            </button>
            @endauth
        </div>
    </div>
</div>
