<div class="offcanvas offcanvas-end" id="options-panel" tabindex="-1" aria-labelledby="options-panel-title">
    <div class="offcanvas-header px-4 py-3">
        <h6 class="offcanvas-title" id="options-panel-title">Options</h6>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body px-2 py-3">
        <div class="list-group">
            <a href="{{route('webapp.composers.show', $piece->composer)}}" class="link-none mb-3 px-3 d-flex d-apart">Meet the composer @icon('chevron-right', ['color' => 'muted', 'mr' => 0, 'ml' => 4])</a>

            @if($piece->siblingsExist())
            <a href="{{route('webapp.pieces.collection', $piece)}}" class="link-none mb-3 px-3 d-flex d-apart">From the same collection @icon('chevron-right', ['color' => 'muted', 'mr' => 0, 'ml' => 4])</a>
            @endif

            <a href="{{route('webapp.pieces.similar', $piece)}}" class="link-none mb-3 px-3 d-flex d-apart">More like this @icon('chevron-right', ['color' => 'muted', 'mr' => 0, 'ml' => 4])</a>

            {{-- <a href="{{route('webapp.pieces.timeline', $piece)}}" class="link-none mb-3 px-3 d-flex d-apart">Timeline @icon('chevron-right', ['color' => 'muted', 'mr' => 0, 'ml' => 4])</a> --}}

            @auth('web')
            <div class="dropdown-divider"></div>

            <div class="py-2 list-group">
                <button type="button" class="btn-raw text-start share-piece link-none mb-3 px-3" data-bs-toggle="modal" data-bs-target="#share-modal">
                    @icon('share') Share this piece
                </button>

                <button type="button" class="btn-raw text-start link-none mb-3 px-3 d-block d-md-none" data-bs-toggle="offcanvas" data-bs-target="#save-to-offcanvas" aria-controls="save-to-offcanvas" data-manage="save-to" data-url="{{route('webapp.pieces.save-to', $piece)}}">
                    @icon('heart', ['color' => 'red']) Manage favorites
                </button>
            </div>
            @endauth
        </div>
    </div>
</div>
