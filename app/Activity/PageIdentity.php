<?php

namespace App\Activity;

use Illuminate\Support\Str;

/** Read-only names for recorded pages, including URLs used before the hub moves. */
class PageIdentity
{
    const HUBS = ['website' => 'Main website', 'learn' => 'Learn', 'blog' => 'Blog', 'shop' => 'Shop',
        'games' => 'Games', 'metronome' => 'Metronome', 'webapp' => 'Web app', 'unknown' => 'Not identified'];

    public static function describe($page)
    {
        $route = $page->route_name ?? '';
        $url = !empty($page->url) ? (parse_url($page->url) ?: []) : [];
        $path = isset($url['path']) ? '/'.trim($url['path'], '/') : null;
        $host = strtolower($url['host'] ?? '');
        $hub = self::hostHub($host, $page->platform ?? null);
        $section = null;
        $kind = null;
        $contentKey = null;
        $name = null;
        $accountRoute = preg_replace('/^webapp\./', '', $route);
        foreach (['login' => 'Sign in', 'register' => 'Registration', 'password.' => 'Password reset',
            'users.profile' => 'Profile', 'users.purchases' => 'My downloads', 'users.orders' => 'Track your orders'] as $prefix => $label) {
            if ($accountRoute === $prefix || strpos($accountRoute, $prefix.'.') === 0 || (substr($prefix, -1) === '.' && strpos($accountRoute, $prefix) === 0)) { $name = $label; break; }
        }

        $learn = [
            'chord-finder' => ['Chord Finder', 'tools.chord-finder.index'],
            'circle-of-fifths' => ['Circle of Fifths', 'tools.circle-of-fifths'],
            'scales' => ['Scales', 'tools.scales.index'], 'arpeggios' => ['Arpeggios', 'tools.arpeggios.index'],
            'quizzes' => ['Quizzes', 'quizzes.index'], 'crashcourses' => ['Crash Courses', 'crashcourses.index'],
            'infographics' => ['Infographics', 'resources.infographs.index'],
            'great-pianists' => ['Great Pianists', 'resources.pianists.index'],
        ];
        $path = $path === null ? null : preg_replace(['#^/tools/(?=chord-finder(?:/|$)|circle-of-fifths(?:/|$)|scales(?:/|$)|arpeggios(?:/|$))#',
            '#^/resources/infographs(?=/|$)#', '#^/resources/great-pianists(?=/|$)#'], ['/', '/infographics', '/great-pianists'], $path);
        foreach ($learn as $slug => [$title, $index]) {
            if ($name !== null) break;
            $prefix = substr($index, 0, strrpos($index, '.') + 1);
            $matches = $route === $index || ($slug !== 'circle-of-fifths' && strpos($route, $prefix) === 0)
                || ($path !== null && preg_match('#^/'.preg_quote($slug, '#').'(?:/|$)#D', $path));
            if (!$matches) continue;
            $hub = 'learn';
            if ($path === null && $route === $index) $path = '/'.$slug;
            $section = $slug;
            $name = $title;
            $tail = $path === null ? '' : substr($path, strlen('/'.$slug));
            if ($tail && $tail !== '/') {
                $name .= ' · '.self::words(trim($tail, '/'));
                if (preg_match('#^/([^/]+)$#D', $tail, $match)) {
                    $kind = ['quizzes' => 'quiz', 'crashcourses' => 'course', 'infographics' => 'infographic', 'great-pianists' => 'pianist'][$slug] ?? null;
                    $contentKey = rawurldecode($match[1]);
                }
            }
            // Analysis/feedback endpoints are distinct pages if an old record
            // calls them a pageview; they are never folded into a tool's views.
            if (in_array($slug, ['chord-finder', 'circle-of-fifths'], true) && $path !== '/'.$slug) $section = null;
            if ($slug === 'quizzes' && (($path !== null && !preg_match('#^/quizzes(?:/[^/]+)?$#D', $path)) || ($path === null && !in_array($route, ['quizzes.index', 'quizzes.show'], true)))) $section = null;
            break;
        }

        if ($name === null && ($hub === 'blog' || strpos($route, 'posts.') === 0 || preg_match('#^/blog(?:/|$)#', $path ?? ''))) {
            $hub = 'blog';
            $path = $path === null ? ($route === 'posts.index' ? '/' : null) : preg_replace('#^/blog(?=/|$)#', '', $path);
            if ($path === '') $path = '/';
            $name = $path === '/' || $route === 'posts.index' ? 'Blog' : 'Blog article';
            if ($path && preg_match('#^/([^/]+)$#D', $path, $match)) {
                $kind = 'post'; $contentKey = rawurldecode($match[1]); $name .= ' · '.self::words($contentKey);
            }
        }
        if ($name === null && ($hub === 'shop' || preg_match('/^(?:ebooks|escores)\./', $route) || preg_match('#^/(?:ebooks|escores)(?:/|$)#', $path ?? ''))) {
            $hub = 'shop'; $name = 'Shop';
            if (preg_match('#^/(ebooks|escores)(?:/([^/]+))?(.*)$#D', $path ?? '', $match)) {
                $name = $match[1] === 'ebooks' ? 'eBooks' : 'eScores';
                if (!empty($match[2])) $name .= ' · '.self::words($match[2]);
                if (!empty($match[3])) $name .= ' · '.self::words(trim($match[3], '/'));
            } elseif ($route === 'ebooks.index' || $route === 'escores.index') {
                $path = '/'.explode('.', $route)[0]; $name = $route === 'ebooks.index' ? 'eBooks' : 'eScores';
            }
        }
        if ($name === null && ($hub === 'games' || strpos($route, 'games.') === 0)) {
            $hub = 'games';
            $path = $path === '/games' ? '/' : $path;
            if ($route === 'games.home' && $path === null) $path = '/';
            $name = $path === '/' || $route === 'games.home' ? 'Games' : self::words(trim($path ?? str_replace(['games.', '.play'], '', $route), '/'));
        }
        if ($name === null && ($hub === 'metronome' || $route === 'metronome.home' || $path === '/metronome')) {
            $hub = 'metronome'; $name = 'Metronome';
            if ($route === 'metronome.home' || $path === '/metronome') $path = '/';
            elseif ($path !== '/') $name = 'Metronome voice settings';
        }
        if ($name === null && $route === 'learn.home') { $hub = 'learn'; $name = 'Learn'; $path = '/'; }
        if ($name === null && $route === 'home') { $hub = 'website'; $name = 'Home'; if ($path === null) $path = '/'; }

        if ($name === null) {
            $names = ['discover' => 'Discover', 'welcome' => 'Home', 'explore' => 'Explore', 'search' => 'Search',
                'favorites' => 'Favorites', 'folder' => 'Collection', 'playlists' => 'Collections', 'my-pieces' => 'My pieces',
                'highlights' => 'Highlights', 'composers' => 'Composers', 'pricing' => 'Premium', 'checkout' => 'Subscription checkout',
                'purchases' => 'My downloads', 'profile' => 'Profile', 'register' => 'Registration', 'login' => 'Sign in',
                'find-your-match' => 'Find your match', 'match' => 'Find your match', 'tour' => 'Find your match',
                'contact' => 'Contact', 'privacy' => 'Privacy policy', 'terms' => 'Terms of service', 'password' => 'Password reset'];
            foreach ($names as $key => $title) if (strpos($route, $key) !== false) { $name = $title; break; }
            if ($hub === 'webapp' && preg_match('#^/pieces/([^/]+)$#D', $path ?? '', $match)) {
                $kind = 'piece'; $contentKey = rawurldecode($match[1]); $name = 'Piece · '.self::words($contentKey);
            }
            if ($name === null) $name = $path === '/' && $hub !== 'unknown' ? ($hub === 'website' ? 'Home' : self::HUBS[$hub])
                : self::words(str_replace('.', ' / ', $route));
        }
        if ($name === '') $name = 'Page not recorded';

        // Only public content paths are useful as page-level dimensions. Account
        // IDs, signed verification/reset paths and query values stay out of tables.
        $publicPath = ($hub === 'learn' && ($path === '/' || $section !== null))
            || ($hub === 'blog' && ($path === '/' || strpos($route, 'posts.') === 0))
            || ($hub === 'shop' && preg_match('#^/(?:ebooks|escores)(?:/[^/]+){0,3}$|^/$#D', $path ?? ''))
            || ($hub === 'games' && ($path === '/' || preg_match('/^games\.[a-z-]+\.play$/D', $route)))
            || ($hub === 'metronome' && $path === '/')
            || ($hub === 'webapp' && ($kind === 'piece' || in_array($path, ['/', '/explore', '/search', '/playlists', '/highlights', '/tour', '/welcome', '/latest'], true)))
            || in_array($path, ['/', '/support', '/contact-us', '/privacy-policy', '/terms-of-service'], true);
        $identity = $publicPath && $path !== null ? $path : 'route:'.($route ?: 'unrecorded');
        return (object) ['key' => $hub.':'.$identity, 'hub' => $hub, 'hub_name' => self::HUBS[$hub],
            'name' => rtrim($name, '. '), 'path' => $publicPath ? $path : null, 'section' => $section,
            'content_type' => $kind, 'content_key' => $contentKey, 'route_name' => $route];
    }

    private static function hostHub($host, $platform)
    {
        if ($platform === 'webapp') return 'webapp';
        $configured = parse_url(preg_match('#^https?://#', config('app.url', '')) ? config('app.url') : 'https://'.config('app.url'), PHP_URL_HOST);
        foreach (array_unique(array_filter([$configured, 'pianolit.com', 'pianolit.test', 'localhost'])) as $base) {
            if ($host === $base || $host === 'www.'.$base) return 'website';
            foreach (['learn', 'blog', 'shop', 'games', 'metronome', 'my'] as $prefix) if ($host === $prefix.'.'.$base) return $prefix === 'my' ? 'webapp' : $prefix;
        }
        return 'unknown';
    }

    private static function words($value)
    {
        return Str::ucfirst(str_replace(['-', '_', '/'], [' ', ' ', ' / '], rawurldecode($value)));
    }
}
