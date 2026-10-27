<?php

namespace Tests\Review;

use App\Activity\{PageIdentity, Presentation};

class HistoricalActivityLabelsTest extends ReviewTestCase
{
    public function test_old_hub_paths_still_have_readable_labels_in_retained_journeys()
    {
        foreach ([
            ['tools.chord-finder.index', 'https://pianolit.com/tools/chord-finder?notes=C,E,G', 'learn', 'Chord Finder'],
            ['tools.circle-of-fifths', 'https://learn.pianolit.com/circle-of-fifths', 'learn', 'Circle of Fifths'],
            ['quizzes.show', 'https://learn.pianolit.com/quizzes/musical-terms', 'learn', 'Quizzes · Musical terms'],
            ['posts.index', 'https://pianolit.com/blog', 'blog', 'Blog'],
            ['metronome.home', 'https://metronome.pianolit.com/', 'metronome', 'Metronome'],
            ['ebooks.index', 'https://shop.pianolit.com/ebooks', 'shop', 'eBooks'],
        ] as [$route, $url, $hub, $name]) {
            $page = (object) ['route_name' => $route, 'url' => $url];
            $this->assertSame($hub, PageIdentity::describe($page)->hub);
            $this->assertSame($name, Presentation::page($page));
        }
        $this->assertSame('Page not recorded', Presentation::page((object) []));
    }

    public function test_sensitive_historical_paths_remain_redacted()
    {
        $page = PageIdentity::describe((object) ['route_name' => 'password.reset',
            'url' => 'https://pianolit.com/password/reset/private-token?email=secret@example.com']);
        $this->assertSame('Password reset', $page->name);
        $this->assertNull($page->path);
        $this->assertStringNotContainsString('private-token', json_encode($page));
        $this->assertStringNotContainsString('secret@example.com', json_encode($page));
    }
}
