@php($signupFreePick = request()->routeIs('webapp.pieces.show') && isset($piece) && $piece->is_free)
@php($signupPolicy = app(\App\Services\WebApp\AccessPolicy::class))
@foreach([
    'video' => ['title' => $signupFreePick ? 'Sign up to keep watching' : 'Sign up to explore videos'],
    'audio' => ['title' => $signupFreePick ? 'Sign up to keep listening' : 'Sign up to explore recordings'],
    'score' => ['title' => 'Sign up to explore scores'],
    'annotations' => ['title' => 'Sign up to annotate this score'],
    'score-export' => ['title' => 'Download and print scores'],
    'favorites' => ['title' => 'Sign up to save your favorites'],
    'escore' => ['title' => 'Sign up to create an eScore'],
    'collections' => ['title' => 'Sign up to explore collections'],
] as $action => $prompt)
    @php($feature = ['video' => 'performance', 'score-export' => 'exports', 'favorites' => 'folder_pieces', 'escore' => 'escores'][$action] ?? $action)
    @php($actionText = ['video' => 'watch the full video', 'audio' => 'listen to the full recording', 'score' => 'read the full score', 'annotations' => 'add your own markings and save them', 'score-export' => 'download and print scores', 'favorites' => 'save your favorite pieces', 'escore' => 'create a practice book from scores', 'collections' => 'explore collections'][$action])
    @php($actionPiece = in_array($action, ['favorites', 'escore', 'collections'], true) ? null : ($piece ?? null))
    @php($prompt['description'] = $signupPolicy->signupDescription($feature, $actionPiece, $actionText))
    @component('components.modal', [
        'id' => 'signup-'.$action.'-modal',
        'centered' => true,
        'descriptionId' => 'signup-'.$action.'-description',
        'options' => ['header' => ['raw' => true], 'body' => ['padding' => 4]],
    ])
        @slot('header')
            <h2 class="h4 mb-0 me-3">{{ $prompt['title'] }}</h2>
        @endslot
        @slot('body')
            <p id="signup-{{ $action }}-description" class="text-muted">{{ $prompt['description'] }}</p>
            <a href="{{ route('register') }}" class="btn btn-primary w-100">SIGN UP</a>
            <p class="small text-muted text-center mb-0 mt-3">Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
        @endslot
    @endcomponent
@endforeach
