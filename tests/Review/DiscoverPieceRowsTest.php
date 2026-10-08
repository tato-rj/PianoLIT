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
            $pieces = collect();
            for ($i = 0; $i < 14; $i++) {
                $piece = create(Piece::class, [
                    'name' => 'Latest fixture '.$i, 'cover_path' => 'fixture.jpg',
                    'videos' => serialize([]), 'audio_path' => 'fixture.mp3',
                    'score_path' => 'fixture.pdf', 'catalogue_name' => 'Op.', 'catalogue_number' => 31,
                    'created_at' => now()->subMinutes(14 - $i),
                ]);
                $piece->tags()->attach([$level->id, $sublevel->id]);
                create(Tutorial::class, ['piece_id' => $piece->id, 'type' => 'Synthesia', 'category' => 'synthesia']);
                $pieces->push($piece);
            }
            create(Tutorial::class, ['piece_id' => $pieces->last()->id, 'type' => 'Performance', 'category' => 'performance']);
            return $pieces;
        });
    }

    public function test_latest_view_all_is_public_paginated_and_newest_first()
    {
        $first = $this->get(route('webapp.latest'))->assertOk();
        $this->assertSame($this->pieces->reverse()->take(12)->pluck('id')->all(), $first->viewData('pieces')->pluck('id')->all());
        $second = $this->get(route('webapp.latest', ['page' => 2, 'user_id' => 999]))->assertOk();
        $this->assertSame($this->pieces->take(2)->reverse()->pluck('id')->all(), $second->viewData('pieces')->pluck('id')->all());
        $this->assertGuest('web');
        $this->assertSame('Latest fixture 13', $this->pieces->last()->fresh()->name);
        $this->withExceptionHandling()->getJson(route('webapp.latest', ['page' => -1]))->assertUnprocessable();
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

    public function test_women_feature_reuses_loaded_composer_and_retains_all_piece_links()
    {
        $cards = PieceCards::load($this->pieces, false);
        $composer = $cards->first()->composer;
        $composer->biography = '<script>alert("unsafe")</script>A biography from the existing composer profile.';
        \DB::enableQueryLog();
        \DB::flushQueryLog();
        $row = ['title' => 'From women composers', 'type' => 'piece', 'content' => $cards];
        $html = view('webapp.discover.rows.gallery', compact('row'))->render();
        $this->assertCount(0, \DB::getQueryLog());
        \DB::disableQueryLog();
        $this->assertStringContainsString(e($composer->name), $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('A biography from the existing composer profile.', $html);
        $this->assertStringContainsString(e(route('webapp.search.results', ['search' => $composer->name, 'model' => \App\Composer::class])), $html);
        $this->assertSame(14, substr_count($html, 'class="discover-compact-card discover-piece-link'));
        foreach ($cards as $piece) $this->assertStringContainsString(route('webapp.pieces.show', $piece), $html);
        $composer->biography = $composer->curiosity = $composer->cover_path = null;
        $html = view('webapp.discover.rows.gallery', compact('row'))->render();
        $this->assertStringContainsString('Explore this composer’s piano repertoire.', $html);
        $this->assertStringContainsString(asset('images/misc/placeholder-image.png'), $html);
        $row['content'] = [];
        $this->assertStringNotContainsString('women-composers-heading', view('webapp.discover.rows.gallery', compact('row'))->render());
    }
}
