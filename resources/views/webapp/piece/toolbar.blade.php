<div class="piece-artwork-toolbar position-absolute top-0 start-0 w-100">
    <div class="container">
        <div class="row">
            <div class="col-lg-10 col-md-12 mx-auto d-flex align-items-center justify-content-between">
                <div class="piece-artwork-pill">
                    @include('webapp.components.back', ['inline' => true])
                </div>
                <div class="piece-artwork-pill">
                    @include('webapp.components.favorite', ['favoriteClasses' => 'piece-artwork-action', 'favoriteIconMargin' => 0, 'favoriteStyle' => 'font-size: 1rem'])
                    <button class="btn-raw text-body piece-artwork-action" type="button" aria-label="Share this piece"
                        data-bs-toggle="modal" data-bs-target="#piece-share-modal" aria-controls="piece-share-modal">
                        @icon('share', ['mr' => 0])
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
