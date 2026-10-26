@php($accessPrompts = app(\App\Services\WebApp\AccessPrompts::class))
@foreach(['video', 'audio', 'score', 'annotations', 'score-export', 'favorites', 'escore', 'collections', 'search'] as $action)
    @php($actionPiece = in_array($action, ['favorites', 'escore', 'collections', 'search'], true) ? null : ($piece ?? null))
    @include('components.account.access-modal', $accessPrompts->prompt($action, 'signup', $actionPiece) + ['id' => 'signup-'.$action.'-modal'])
@endforeach
