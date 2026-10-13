<?php

namespace Tests\Review;

use App\{Piece, Tag, Tutorial, User};
use App\Services\WebApp\PieceCards;
use Illuminate\Database\Eloquent\Model;

class DiscoverPieceRowsTest extends ReviewTestCase
{
    protected $pieces;

    public function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        $this->pieces = Model::withoutEvents(function () {
            $level = create(Tag::class, ['type' => 'level', 'name' => 'intermediate']);
            $sublevel = create(Tag::class, ['type' => 'sublevel', 'name' => 'late intermediate']);
            $period = create(Tag::class, ['type' => 'period', 'name' => 'romantic']);
            $pieces = collect();
            for ($i = 0; $i < 14; $i++) {
                $piece = create(Piece::class, [
                    'name' => 'Latest fixture '.$i, 'cover_path' => 'fixture.jpg',
                    'videos' => serialize([]), 'audio_path' => 'fixture.mp3',
                    'score_path' => 'fixture.pdf', 'catalogue_name' => 'Op.', 'catalogue_number' => 31,
                    'created_at' => now()->subMinutes(14 - $i),
                ]);
                $piece->tags()->attach([$level->id, $sublevel->id, $period->id]);
                create(Tutorial::class, ['piece_id' => $piece->id, 'type' => 'Synthesia', 'category' => 'synthesia']);
                $pieces->push($piece);
            }
            create(Tutorial::class, ['piece_id' => $pieces->last()->id, 'type' => 'Performance', 'category' => 'performance']);
            return $pieces;
        });
    }

    public function test_latest_view_all_opens_regular_search_with_an_empty_input()
    {
        $url = route('webapp.search.results', ['catalogue' => 1, 'search' => '']);
        $row = ['title' => 'Latest pieces', 'content' => []];
        $html = view('webapp.discover.rows.latest', compact('row'))->render();
        $this->assertStringContainsString('href="'.e($url).'"', $html);
        $response = $this->get($url)->assertOk()->assertViewIs('webapp.search.index');
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $input = (new \DOMXPath($dom))->query('//form[@id="search-form"]//input[@name="search"]')->item(0);
        $this->assertSame('', $input->getAttribute('value'));
        $this->get(route('webapp.latest'))->assertStatus(301)->assertRedirect($url);
        $this->get(route('webapp.latest', ['page' => 2, 'user_id' => 999]))->assertStatus(301)->assertRedirect($url);
        $this->assertGuest('web');
    }

    public function test_latest_view_all_uses_the_whole_catalogue_in_newest_order_with_regular_search_limits()
    {
        // All pieces belong in search, including ones absent from the video-only rail.
        $this->pieces->first()->tutorials()->delete();
        $this->pieces[12]->update(['created_at' => $this->pieces[13]->created_at]);
        $params = ['catalogue' => 1, 'search' => '', 'lazy-load' => '', 'include_total' => 1];
        $expected = $this->pieces->reverse()->values();
        $guest = $this->getJson(route('webapp.search.results', $params))->assertOk()->assertHeader('X-Search-Total', '14');
        $guest->assertSeeInOrder($expected->take(3)->pluck('name')->all())->assertSee('Sign up to see more.');
        $this->assertSame(3, substr_count($guest->getContent(), 'data-sort-name='));
        $this->assertSame('', $this->getJson(route('webapp.search.results', $params + ['page' => 2]))->assertOk()->getContent());

        $this->actingAs(Model::withoutEvents(function () { return create(User::class); }), 'web');
        foreach ([1, 2] as $page) {
            $pieces = $expected->forPage($page, 10);
            $response = $this->getJson(route('webapp.search.results', $params + ['page' => $page]))->assertOk();
            $response->assertSeeInOrder($pieces->pluck('name')->all())->assertDontSee('data-search-signup');
            $this->assertSame($pieces->count(), substr_count($response->getContent(), 'data-sort-name='));
        }
    }

    public function test_latest_cards_show_level_and_only_available_media_icons()
    {
        $cards = PieceCards::load($this->pieces->take(1)->push($this->pieces->last()), false);
        foreach ($cards as $index => $piece) {
            $html = view('webapp.discover.cards.latest-piece', compact('piece'))->render();
            $dom = new \DOMDocument;
            @$dom->loadHTML($html);
            $xpath = new \DOMXPath($dom);
            $this->assertStringContainsString('Late intermediate', $html);
            $this->assertStringContainsString('color-intermediate', $html);
            $this->assertSame(1, $xpath->query('//i[@title="Audio"]')->length);
            $this->assertSame($index === 0 ? 0 : 1, $xpath->query('//i[@title="Video"]')->length);
            $this->assertSame(1, $xpath->query('//i[@title="Score"]')->length);
            $this->assertSame(1, $xpath->query('//i[@title="Synthesia"]')->length);
            $this->assertSame('', trim($xpath->query('//ul')->item(0)->textContent));
        }
        $piece = $cards->first();
        $piece->audio_path = null;
        $piece->score_path = null;
        $piece->webapp_has_synthesia = false;
        $html = view('webapp.discover.cards.latest-piece', compact('piece'))->render();
        $this->assertStringNotContainsString('title="Audio"', $html);
        $this->assertStringNotContainsString('title="Score"', $html);
        $this->assertStringNotContainsString('title="Synthesia"', $html);
        $piece->cover_path = null;
        $piece->setRelation('tags', collect());
        $html = view('webapp.discover.cards.latest-piece', compact('piece'))->render();
        $this->assertStringContainsString(asset('images/webapp/thumbnail.jpg'), $html);
        $this->assertStringNotContainsString('icon-circle', $html);
        $html = view('webapp.discover.rows.recently-viewed', ['row' => ['title' => 'Recently viewed', 'content' => [$piece]]])->render();
        $this->assertStringContainsString(asset('images/webapp/thumbnail.jpg'), $html);
    }

    public function test_piece_card_analytics_labels_contain_only_the_piece_name_and_composer()
    {
        $piece = PieceCards::load($this->pieces->take(1), false)->first();
        $row = ['title' => 'Recently viewed', 'content' => collect([$piece])];
        foreach (['webapp.components.piece', 'webapp.components.piece.highlight', 'webapp.discover.cards.latest-piece',
            'webapp.discover.cards.compact-piece', 'webapp.discover.rows.recently-viewed'] as $view) {
            $html = view($view, ['piece' => $piece, 'row' => $row, 'hasFullAccess' => true, 'freePick' => true])->render();
            $dom = new \DOMDocument; @$dom->loadHTML($html);
            $element = (new \DOMXPath($dom))->query('//*[@data-activity-label]')->item(0);
            $this->assertNotNull($element, $view);
            $this->assertSame($piece->medium_name_with_composer, $element->getAttribute('data-activity-label'), $view);
            $this->assertStringNotContainsString('intermediate', $element->getAttribute('data-activity-label'));
        }
    }

    public function test_latest_cards_keep_the_new_badge_for_the_existing_five_day_window()
    {
        \Illuminate\Support\Carbon::setTestNow('2026-10-11 20:45:00');
        try {
            $piece = PieceCards::load($this->pieces->take(1), false)->first();
            foreach ([
                [now(), 1],
                [now()->subDays(5), 1],
                [now()->subDays(5)->subSecond(), 0],
                [now()->subDays(6), 0],
            ] as [$createdAt, $expectedBadges]) {
                $piece->created_at = $createdAt;
                $html = view('webapp.discover.cards.latest-piece', compact('piece'))->render();
                $dom = new \DOMDocument;
                @$dom->loadHTML($html);
                $badges = (new \DOMXPath($dom))->query('//a//span[normalize-space(.)="NEW"]');
                $this->assertSame($expectedBadges, $badges->length, 'Piece created at '.$createdAt);
            }
        } finally {
            \Illuminate\Support\Carbon::setTestNow();
        }
    }

    public function test_for_you_requires_a_web_account_and_omits_unavailable_media()
    {
        $row = ['title' => 'For you', 'type' => 'piece', 'content' => PieceCards::load($this->pieces->take(1)->push($this->pieces->last()), false)];
        $this->assertStringNotContainsString('for-you-heading', view('webapp.discover.rows.gallery', compact('row'))->render());
        $this->actingAs(Model::withoutEvents(function () { return create(User::class); }), 'web');
        $row['content'][1]->audio_path = null;
        $row['content'][1]->score_path = null;
        $html = view('webapp.discover.rows.gallery', compact('row'))->render();
        $this->assertSame(2, substr_count($html, 'class="discover-compact-card discover-piece-link'));
        $this->assertSame(1, substr_count($html, 'icon-headphones'));
        $this->assertSame(1, substr_count($html, 'icon-file-text'));
        $this->assertSame(1, substr_count($html, 'icon-video'));
        $this->assertSame(2, substr_count($html, 'icon-flame'));
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        $media = (new \DOMXPath($dom))->query('//ul[@aria-label="Available media"]');
        foreach ($media as $icons) {
            $this->assertSame('', trim($icons->textContent));
            foreach ($icons->getElementsByTagName('i') as $icon) {
                $this->assertSame($icon->getAttribute('title'), $icon->getAttribute('aria-label'));
            }
        }
        foreach ($row['content'] as $piece) $this->assertStringContainsString(route('webapp.pieces.show', $piece), $html);
        $row['content'] = [];
        $this->assertStringNotContainsString('for-you-heading', view('webapp.discover.rows.gallery', compact('row'))->render());
    }

    public function composerFeatureRows()
    {
        return [
            ['From women composers', 'women-composers-heading'],
        ];
    }

    /** @dataProvider composerFeatureRows */
    public function test_composer_feature_reuses_loaded_composer_and_retains_all_piece_links($title, $heading)
    {
        $cards = PieceCards::load($this->pieces, false);
        $composer = $cards->first()->composer;
        $composer->biography = '<script>alert("unsafe")</script>A biography from the existing composer profile.';
        \DB::enableQueryLog();
        \DB::flushQueryLog();
        $row = ['title' => $title, 'type' => 'piece', 'content' => $cards];
        $html = view('webapp.discover.rows.gallery', compact('row'))->render();
        $this->assertCount(0, \DB::getQueryLog());
        \DB::disableQueryLog();
        $this->assertStringContainsString(e($composer->name), $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('A biography from the existing composer profile.', $html);
        $this->assertStringContainsString(e(route('webapp.search.results', ['search' => $composer->name, 'model' => \App\Composer::class])), $html);
        $this->assertSame(14, substr_count($html, 'class="discover-compact-card discover-piece-link'));
        $this->assertStringContainsString('id="'.$heading.'"', $html);
        $this->assertSame(14, substr_count($html, 'Late intermediate'));
        foreach ($cards as $piece) $this->assertStringContainsString(route('webapp.pieces.show', $piece), $html);
        $composer->biography = $composer->curiosity = $composer->cover_path = null;
        $html = view('webapp.discover.rows.gallery', compact('row'))->render();
        $this->assertStringContainsString('Explore this composer’s piano repertoire.', $html);
        $this->assertStringContainsString(asset('images/misc/placeholder-image.png'), $html);
        $row['content'] = [];
        $this->assertStringNotContainsString($heading, view('webapp.discover.rows.gallery', compact('row'))->render());
    }
}
