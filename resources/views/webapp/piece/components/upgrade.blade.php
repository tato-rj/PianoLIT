<div class="modal fade" id="piece-upgrade-modal" tabindex="-1" role="dialog" aria-labelledby="piece-upgrade-title" aria-describedby="piece-upgrade-description" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0">
            <button class="close piece-upgrade-close" type="button" data-bs-dismiss="modal" aria-label="Close premium prompt">
                @icon('close', ['mr' => 0, 'attributes' => ['aria-hidden' => 'true']])
            </button>

            <div class="modal-body">
                <div class="text-center piece-upgrade-heading">
                    <h2 id="piece-upgrade-title">Go Premium</h2>
                    <p id="piece-upgrade-description" class="text-muted mb-0">Get full access to this piece and hundreds more.</p>
                </div>

                <ul class="piece-upgrade-benefits list-unstyled">
                    @foreach([
                        ['icon' => 'play', 'color' => 'orange', 'title' => 'Full-length videos', 'description' => 'Watch complete performances with multiple camera angles.'],
                        ['icon' => 'layers', 'color' => 'green', 'title' => 'A world of repertoire', 'description' => 'Explore pieces across all levels, styles, and moods.'],
                        ['icon' => 'music', 'color' => 'blue', 'title' => 'Read and annotate scores', 'description' => 'Follow the score and add your own practice markings.'],
                        ['icon' => 'compass', 'color' => 'pink', 'title' => 'Discover new composers', 'description' => 'Find your next favorite, from familiar names to hidden gems.'],
                        ['icon' => 'sliders-horizontal', 'color' => 'purple', 'title' => 'Synthesia & audio', 'description' => 'Follow falling notes and listen to piano recordings.'],
                        ['icon' => 'folder-open', 'color' => 'indigo', 'title' => 'Organize your repertoire', 'description' => 'Save your favorites and create collections for your practice.'],
                    ] as $benefit)
                        <li class="piece-upgrade-benefit">
                            <span class="piece-upgrade-icon piece-upgrade-icon--{{ $benefit['color'] }}" aria-hidden="true">
                                @icon($benefit['icon'], ['mr' => 0])
                            </span>
                            <div>
                                <h3 class="h6 mb-1">{{ $benefit['title'] }}</h3>
                                <p class="text-muted mb-0">{{ $benefit['description'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <div class="text-center piece-upgrade-action">
                    <a href="{{ route('webapp.membership.pricing') }}" class="btn btn-primary rounded-pill btn-wide">
                        @icon('crown', ['mr' => 2, 'attributes' => ['aria-hidden' => 'true']])GO PREMIUM
                    </a>
                    <p class="small text-muted mb-0 mt-3">Instant access. Cancel anytime.</p>
                </div>
            </div>
        </div>
    </div>
</div>
