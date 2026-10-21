<?php

namespace Tests\Review;

use App\{Admin, Composer, Piece, Timeline};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Http, Redis};

class RetiredPublicResourcesTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Redis::shouldReceive('get')->andReturn(null);
    }

    public static function retiredPaths()
    {
        return [['/resources/timeline'], ['/composers/birthdays'], ['/true-or-false'], ['/true-or-false/feedback?score=1&count=2'], ['/riddles']];
    }

    /** @dataProvider retiredPaths */
    public function test_retired_public_pages_and_feedback_no_longer_have_routes($path)
    {
        $this->withExceptionHandling();
        foreach (['localhost', 'www.localhost', 'my.localhost', 'learn.localhost'] as $host) {
            $response = $this->get('http://'.$host.$path);
            if ($host === 'my.localhost' && strpos($path, '/composers/') !== 0) {
                $response->assertRedirect(route('webapp.discover')); // Existing web-app not-found behavior.
            } else {
                $response->assertNotFound();
            }
        }
        foreach (['resources.timeline', 'composers.birthdays', 'true-or-false.index', 'true-or-false.feedback', 'riddles'] as $name) {
            $this->assertNull(app('router')->getRoutes()->getByName($name));
        }
    }

    public function test_navigation_contains_only_the_four_retained_learning_sections()
    {
        $response = $this->get(route('resources.pianists.index'))->assertOk();
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        foreach (['Music Timeline', 'Famous birthdays', 'True or False', 'Riddles'] as $label) {
            $this->assertSame(0, $xpath->query('//nav//a[contains(., "'.$label.'")]')->length);
        }
        foreach (['Crash Courses' => '/crashcourses', 'Infographics' => '/infographics', 'Great Pianists' => '/great-pianists', 'Quizzes' => '/quizzes'] as $label => $path) {
            $this->assertSame(1, $xpath->query('//nav//a[@href="http://learn.localhost'.$path.'" and normalize-space(.)="'.$label.'"]')->length);
        }
    }

    public function test_dedicated_files_views_mail_commands_and_published_assets_are_removed()
    {
        foreach (['timeline.index', 'composers.birthdays.index', 'games.trueorfalse.index', 'games.riddles.index', 'emails.timeline.daily', 'promotions.birthdays'] as $view) {
            $this->assertFalse(view()->exists($view));
        }
        foreach (['app/Http/Controllers/TimelineController.php', 'app/Http/Controllers/ComposersController.php', 'app/Http/Controllers/GamesController.php',
            'app/Games/TrueOrFalse/TrueOrFalse.php', 'app/Games/TrueOrFalse/Statements.php', 'app/Mail/BirthdaysEmail.php', 'app/Mail/Timeline/OnThisDay.php',
            'app/Console/Commands/SendTimelineEmail.php', 'resources/js/views/timeline.js', 'public/js/views/timeline.js', 'public/js/views/timeline 2.js'] as $path) {
            $this->assertFileDoesNotExist(base_path($path));
        }
        $manifest = json_decode(file_get_contents(public_path('mix-manifest.json')), true);
        foreach ($manifest as $asset => $url) {
            $this->assertFalse(strpos($asset, '/images/riddles/') === 0);
            $this->assertStringNotContainsString('birthdays.jpg', $asset);
        }
        $this->assertArrayNotHasKey('/js/views/timeline.js', $manifest);
        $this->assertFalse(method_exists(Timeline::class, 'scopeGenerate'));
        $this->assertTrue(method_exists(Timeline::class, 'scopeFor'));
    }

    public function test_mobile_timeline_route_and_json_keep_their_existing_shape_and_records()
    {
        [$piece, $event] = Model::withoutEvents(function () {
            $composer = create(Composer::class, ['date_of_birth' => '1700-01-01', 'date_of_death' => '1750-01-01', 'is_famous' => false]);
            $piece = create(Piece::class, ['composer_id' => $composer->id, 'composed_in' => 1800]);
            $event = Timeline::create(['creator_id' => create(Admin::class)->id, 'year' => 1799, 'type' => 'history', 'event' => 'Shared mobile history']);
            return [$piece, $event];
        });
        $this->assertSame('1700', $event->fresh()->toArray()['century']);
        $response = $this->getJson(route('api.pieces.timeline', $piece->id))->assertOk();
        $this->assertSame(['year' => 1799, 'event' => 'Shared mobile history', 'highlight' => false], $response->json()[0]);
        $this->assertSame(1800, $response->json()[1]['year']);
        $this->assertTrue($response->json()[1]['highlight']);
        $this->assertSame(['year', 'event', 'highlight'], array_keys($response->json()[1]));
        $this->assertDatabaseCount('timelines', 1);
        $this->assertNotNull(app('router')->getRoutes()->getByName('webapp.pieces.timeline'));
        $this->assertNotNull(app('router')->getRoutes()->getByName('admin.timeline-events.index'));
        $this->assertTrue(view()->exists('admin.pages.analytics.composers.birthdays'));
        Http::assertNothingSent();
    }
}
