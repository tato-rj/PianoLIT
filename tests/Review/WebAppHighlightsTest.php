<?php

namespace Tests\Review;

use App\{Composer, Piece, Tag, User};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Redis};

class WebAppHighlightsTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Redis::shouldReceive('get')->andReturn(null);
        $this->withoutMiddleware([
            \App\Http\Middleware\Logs\RecordWebAppLog::class,
            \App\Http\Middleware\UpdateLocation::class,
        ]);
    }

    private function catalogue($size = 24)
    {
        return Model::withoutEvents(function () use ($size) {
            $composer = create(Composer::class);
            $period = create(Tag::class, ['name' => 'baroque', 'type' => 'period']);
            $easy = create(Tag::class, ['name' => 'elementary', 'type' => 'level', 'order' => 1]);
            $hard = create(Tag::class, ['name' => 'advanced', 'type' => 'level', 'order' => 6]);
            $pieces = collect();
            for ($i = 0; $i < $size; $i++) {
                $piece = create(Piece::class, ['name' => 'Highlight '.$i, 'composer_id' => $composer->id,
                    'highlighted_at' => now()->subDays($i), 'is_free' => $i === 0,
                    'cover_path' => 'highlights-test-'.$i.'.jpg']);
                $piece->tags()->attach([$period->id, $i % 2 ? $hard->id : $easy->id]);
                $pieces->push($piece);
            }
            create(Piece::class, ['name' => 'Unfeatured piece', 'composer_id' => $composer->id, 'highlighted_at' => null]);
            return $pieces;
        });
    }

    public function test_initial_page_has_all_sortable_cards_and_deferred_artwork()
    {
        $pieces = $this->catalogue(120);
        $response = $this->get(route('webapp.highlights'))->assertOk()->assertDontSee('Unfeatured piece');
        $html = $response->getContent();
        $this->assertSame(120, substr_count($html, 'data-sort-views='));
        $this->assertSame(117, substr_count($html, 'loading="lazy"'));
        $this->assertSame(3, substr_count($html, 'loading="eager"'));
        $this->assertSame(120, substr_count($html, 'width="440" height="220"'));
        $this->assertStringNotContainsString('background-image: url(', $html);
        $this->assertTrue($pieces[0]->hasWebMediaAccess());
        $this->assertFalse($pieces[1]->hasWebMediaAccess());
        if ($path = getenv('HIGHLIGHTS_PREVIEW_PATH')) file_put_contents($path, $html);
    }

    public function test_fragment_filters_all_picks_and_queries_only_card_data()
    {
        $this->catalogue();
        DB::enableQueryLog();
        DB::flushQueryLog();
        $response = $this->getJson(route('webapp.highlights', ['filters' => ['["elementary"]', '["baroque"]']]))->assertOk();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertCount(3, $queries);
        $sql = implode(' ', array_column($queries, 'query'));
        foreach (['favorites_count', 'tags_count', 'tutorials_count', 'pieces_count', 'countries'] as $unused) {
            $this->assertStringNotContainsString($unused, $sql);
        }
        $this->assertStringContainsString('views_count', $sql);
        $this->assertSame(12, substr_count($response->getContent(), 'data-sort-views='));
        $response->assertDontSee('<html', false)->assertDontSee('Unfeatured piece');
        $empty = $this->getJson(route('webapp.highlights', ['filters' => ['["missing"]']]))->assertOk();
        $this->assertSame('', $empty->getContent());
        $user = Model::withoutEvents(function () { return create(User::class); });
        $member = $this->actingAs($user, 'web')->get(route('webapp.highlights'))->assertOk();
        $this->assertSame(24, substr_count($member->getContent(), 'data-sort-views='));
    }

    public function test_invalid_filter_shapes_are_rejected()
    {
        $this->withExceptionHandling();
        foreach (['bad', ['bad'], ['null'], ['42'], ['{}'], ['[{}]'], [['elementary']], array_fill(0, 6, '["baroque"]')] as $filters) {
            $this->getJson(route('webapp.highlights', compact('filters')))->assertStatus(422);
        }
    }
}
