@php
    $status = (int) ($status ?? (isset($exception) ? $exception->getStatusCode() : 500));
    $messages = [
        400 => ['Let’s try that again', 'We couldn’t read that request. Go back and give it another try.', 'Request not understood', 'paper'],
        401 => ['A quick introduction first', 'Sign in to your PianoLIT account to continue to this page.', 'Sign-in needed', 'peek'],
        402 => ['One more step', 'This page needs a payment before you can continue. Return home to review your options.', 'Payment required', 'peek'],
        403 => ['This one’s behind the scenes', 'You don’t have access to this page with your current account. You can return home or contact us for help.', 'Page restricted', 'peek'],
        404 => ['This page wandered off', 'We couldn’t find the page you’re looking for. The link may be old, or the address may have a typo.', 'Page not found', 'peek'],
        405 => ['A small detour', 'That action isn’t available here. Go back to the previous page and try from there.', 'Action unavailable', 'paper'],
        408 => ['That took a little too long', 'The request timed out. Head back and try again when you’re ready.', 'Request timed out', 'rest'],
        410 => ['This page has left the building', 'This page is no longer available. Head home to find something else to explore.', 'Page no longer available', 'peek'],
        413 => ['A little too much to carry', 'That upload is larger than we can accept. Try a smaller file, or send fewer files at a time.', 'Upload too large', 'stack'],
        419 => ['Let’s start fresh', 'This page’s session has expired. Open the page again before trying to submit your form.', 'Page expired', 'rest'],
        422 => ['Almost there', 'We couldn’t use the information in that request. Go back, check the details and try again.', 'Check your request', 'paper'],
        429 => ['One at a time, please', 'A few too many requests arrived at once. Wait a little before trying again.', 'A short pause', 'rest'],
        500 => ['A little hiccup', 'We couldn’t load this page just now. Give it another try in a moment, or head home.', 'Page unavailable', 'paper'],
        502 => ['A little hiccup', 'We didn’t get the response this page needed. Please try again in a moment.', 'Page unavailable', 'paper'],
        503 => ['Taking a short breather', 'PianoLIT is temporarily unavailable. Please try again in a little while.', 'Temporarily unavailable', 'rest'],
        504 => ['That took a little too long', 'This page took too long to respond. Please try again in a moment.', 'Page timed out', 'rest'],
    ];
    [$heading, $description, $detail, $scene] = $messages[$status] ?? ['A small detour', 'We couldn’t complete that request. Head home, or contact us if you need a hand.', 'Request unavailable', 'paper'];
    $context = \App\Support\ErrorPage::context($errorRequest ?? request(), $status);
    if ($sessionChanged ?? false) {
        $heading = 'Let’s get you back in';
        $description = 'Your session changed in another tab. Open this page again to use your current login.';
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="color-scheme" content="light">
    <title>{{ $detail }} · {{ $status }} · PianoLIT</title>
    <link rel="icon" type="image/svg+xml" href="/images/brand/{{ $context['admin'] ? 'admin-icon' : 'app-icon' }}.svg">
    {{-- Self-contained so an unavailable CDN, session or asset manifest cannot hide recovery. --}}
    <style>{!! file_get_contents(resource_path('views/errors/styles.css')) !!}</style>
</head>
<body class="error-page">
    <header class="error-page__header">
        <a class="error-page__brand" href="/" aria-label="{{ $context['admin'] ? 'PianoLIT admin home' : 'PianoLIT home' }}">
            @brandIcon(['admin' => $context['admin'], 'standalone' => true])
            <span>PianoLIT @if($context['admin'])<small>Admin</small>@endif</span>
        </a>
    </header>
    <main class="error-page__main">
        <div class="error-page__content">
            @include('errors.illustration', ['scene' => $scene])
            <p class="error-page__status"><span>{{ $status }}</span><span aria-hidden="true">·</span>{{ $detail }}</p>
            <h1>{{ $heading }}</h1>
            <p class="error-page__description">{{ $description }}</p>
            <nav class="error-page__actions" aria-label="Page recovery">
                @button(['href' => $context['primary']['url'], 'label' => $context['primary']['label'], 'styles' => ['theme' => 'primary']])
                @if($context['primary']['url'] !== '/')
                    @button(['href' => '/', 'label' => $context['homeLabel'], 'styles' => ['theme' => 'secondary']])
                @endif
            </nav>
            @if(($sessionChanged ?? false) && $context['primary']['url'] !== '/login?restart_session=1')
                <p class="error-page__session">Still can’t continue? <a href="/login?restart_session=1">Sign in again</a></p>
            @endif
        </div>
    </main>
    <footer class="error-page__footer">Need a hand? <a href="mailto:{{ config('app.emails.general', 'contact@pianolit.com') }}">Contact us</a></footer>
</body>
</html>
