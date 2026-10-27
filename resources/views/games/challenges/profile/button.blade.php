@php($playerCircleClasses = 'account-circle games-player-circle btn-raw d-inline-flex align-items-center justify-content-center rounded-circle')
@if($designSystemPreview ?? false)
    <span class="{{ $playerCircleClasses }} account-menu__avatar" data-account-menu-tone="yellow" aria-hidden="true">
        @icon('trophy', ['mr' => 0])
    </span>
@else
    <button type="button" class="{{ $playerCircleClasses }}"
            id="games-player-menu-button" aria-label="Game player menu" title="Game player"
            data-bs-toggle="dropdown" data-bs-display="static" data-account-menu-toggle data-account-menu-tone="yellow"
            aria-haspopup="true" aria-expanded="false">
        @icon('trophy', ['mr' => 0])
    </button>
@endif
