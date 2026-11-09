<?php

namespace Tests\Review;

use App\{Composer, Piece, Tag, User};
use App\Services\WebApp\PieceRecommendations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PieceRecommendationsTest extends ReviewTestCase
{
    protected $piece, $tags, $composer;

    public function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        Model::withoutEvents(function () {
            $this->composer = create(Composer::class, ['name' => 'Clara Schumann']);
            $this->tags = collect([
                'level' => ['elementary', 'level'], 'period' => ['baroque', 'period'],
                'length' => ['short', 'length'], 'happy' => ['happy', 'mood'],
                'dreamy' => ['dreamy', 'mood'], 'sublevel' => ['early beginner', 'sublevel'],
                'otherLevel' => ['advanced', 'level'], 'otherPeriod' => ['romantic', 'period'],
                'decoy' => ['happy', 'technique'],
            ])->map(function ($tag) { return create(Tag::class, ['name' => $tag[0], 'type' => $tag[1]]); });
            $this->piece = $this->makePiece('Current piece', ['happy', 'dreamy', 'sublevel']);
        });
    }

    protected function makePiece($name, array $tags, $level = 'level', $period = 'period')
    {
        return Model::withoutEvents(function () use ($name, $tags, $level, $period) {
            $piece = create(Piece::class, ['name' => $name, 'composer_id' => $this->composer->id, 'description' => 'An expressive melody with a gentle accompaniment.']);
            $piece->tags()->attach($this->tags->only(array_merge([$level, $period, 'length'], $tags))->pluck('id'));
            return $piece;
        });
    }

    public function test_rows_match_the_selected_mood_level_and_period_and_exclude_the_current_piece()
    {
        $happy = $this->makePiece('Happy melody', ['happy'], 'otherLevel', 'otherPeriod');
        $dreamy = $this->makePiece('Dreamy nocturne', ['dreamy'], 'otherLevel', 'otherPeriod');
        $difficulty = $this->makePiece('Elementary waltz', ['decoy'], 'level', 'otherPeriod');
        $period = $this->makePiece('Baroque dance', [], 'otherLevel');
        $more = $this->makePiece('Another happy piece', ['happy']);

        $rows = (new PieceRecommendations)->rows($this->piece)->keyBy('title');
        $this->assertSame(['Best matches', 'Similar mood', 'Similar difficulty', 'Same period', 'Same composer'], $rows->keys()->all());
        $this->assertSame([$more->id], $rows['Best matches']['pieces']->pluck('id')->all());
        $mood = $rows['Similar mood'];
        $this->assertContains($mood['term'], ['happy', 'dreamy']);
        $expectedMood = $mood['term'] === 'happy' ? [$happy->id, $more->id] : [$dreamy->id];
        $this->assertEqualsCanonicalizing($expectedMood, $mood['pieces']->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$difficulty->id, $more->id], $rows['Similar difficulty']['pieces']->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$period->id, $more->id], $rows['Same period']['pieces']->pluck('id')->all());
        $this->assertSame('elementary', $rows['Similar difficulty']['term']);
        $this->assertSame('baroque', $rows['Same period']['term']);
        $this->assertEqualsCanonicalizing([$happy->id, $dreamy->id, $difficulty->id, $period->id, $more->id], $rows['Same composer']['pieces']->pluck('id')->all());
        foreach ($rows as $row) {
            $this->assertNotContains($this->piece->id, $row['pieces']->pluck('id'));
            foreach ($row['pieces'] as $card) {
                $this->assertArrayNotHasKey('webapp_has_performances', $card->getAttributes());
                $this->assertArrayHasKey('webapp_has_synthesia', $card->getAttributes());
            }
        }
    }

    public function test_galleries_are_bounded_and_queries_do_not_grow_with_card_count()
    {
        $collection = ['collection_name' => 'Test collection', 'catalogue_name' => 'Op.', 'catalogue_number' => 1];
        $this->piece->update($collection);
        $counts = [];
        $created = 0;
        foreach ([2, 20] as $size) {
            for (; $created < $size; $created++) $this->makePiece('Gallery piece '.$created, ['happy', 'dreamy'])->update($collection);
            DB::enableQueryLog();
            DB::flushQueryLog();
            $rows = (new PieceRecommendations)->rows($this->piece->fresh());
            $counts[] = count(DB::getQueryLog());
            DB::disableQueryLog();
            foreach ($rows as $row) $this->assertCount(min($size, 6), $row['pieces']);
        }
        $this->assertSame($counts[0], $counts[1]);
        $this->assertLessThanOrEqual(18, $counts[1]);
    }

    public function test_guest_and_signed_in_tabs_show_four_stacked_cards_and_links_to_more_results()
    {
        foreach (['Minuet in G', 'Little prelude', 'Morning song', 'Evening dance', 'Waltz', 'March'] as $name) {
            $this->makePiece($name, ['happy', 'dreamy']);
        }
        $user = Model::withoutEvents(function () { return create(User::class)->setAppends(['full_name']); });
        foreach ([false, true] as $signedIn) {
            if ($signedIn) $this->actingAs($user, 'web');
            $response = $this->get(route('webapp.pieces.show', $this->piece))->assertOk();
            $rows = $response->viewData('recommendationRows');
            $xpath = $this->xpath($response->getContent());
            $this->assertSame(0, $xpath->query('//section[@aria-labelledby="piece-collection-heading"]')->length);
            $this->assertSame(1, $xpath->query('//section[@aria-labelledby="piece-recommendations-heading"]')->length);
            $tabs = $xpath->query('//div[@role="tablist" and @aria-label="More like this"]/button');
            $this->assertSame(['Best matches', 'Similar mood', 'Similar difficulty', 'Same period', 'Same composer'], array_map(function ($tab) { return trim($tab->textContent); }, iterator_to_array($tabs)));
            $this->assertSame('true', $tabs[0]->getAttribute('aria-selected'));
            $this->assertSame(1, $xpath->query('//div[@aria-label="More like this"]/button[@aria-selected="true"]')->length);
            foreach ($rows as $index => $row) {
                $id = 'piece-recommendations-'.$row['key'];
                $panel = $xpath->query('//div[@id="'.$id.'"]')->item(0);
                $this->assertSame($id.'-tab', $panel->getAttribute('aria-labelledby'));
                $this->assertSame($index === 0 ? 'tab-pane active' : 'tab-pane ', $panel->getAttribute('class'));
                $links = $xpath->query('//div[@id="'.$id.'"]//a[contains(concat(" ",normalize-space(@class)," ")," discover-compact-card ")]');
                $this->assertCount(4, $links);
                $this->assertSame(4, $xpath->query('//div[@id="'.$id.'"]/ul/li')->length);
                $this->assertSame(0, $xpath->query('//div[@id="'.$id.'"]//*[contains(@class,"card-scroll-row") or contains(@class,"dragscroll")]')->length);
                foreach ($row['pieces']->take(4) as $cardIndex => $card) {
                    $this->assertSame(route('webapp.pieces.show', $card), $links[$cardIndex]->getAttribute('href'));
                    $this->assertStringContainsString($card->name, $links[$cardIndex]->textContent);
                    $this->assertStringContainsString($card->composer->short_name, $links[$cardIndex]->textContent);
                    $this->assertStringContainsString(ucfirst($card->extended_level_name), $links[$cardIndex]->textContent);
                }
                $more = $xpath->query('//div[@id="'.$id.'"]/a')->item(0);
                $url = $row['key'] === 'same-composer'
                    ? route('webapp.search.results', ['search' => $this->composer->name, 'model' => Composer::class])
                    : $row['url'];
                $this->assertSame($url, $more->getAttribute('href'));
                $this->assertSame('See more', trim($more->textContent));
                if ($row['key'] === 'best-matches') {
                    $this->assertCount(6, $this->get($url)->assertOk()->viewData('similar'));
                    continue;
                }
                $term = $row['term'] ?? $this->composer->name;
                $this->get($url)->assertOk()->assertSee('name="search" value="'.$term.'"', false);
                $results = $this->getJson($url)->assertOk();
                $this->assertSame($signedIn ? 7 : 3, substr_count($results->getContent(), 'data-sort-name='));
            }
        }
    }

    public function test_collection_row_requires_actual_siblings_and_uses_large_cards_in_collection_order()
    {
        $metadata = ['collection_name' => 'Album for the Young', 'catalogue_name' => 'Op.', 'catalogue_number' => 68];
        $this->piece->update($metadata);
        $this->assertNull((new PieceRecommendations)->rows($this->piece)->firstWhere('key', 'collection'));
        $later = $this->makePiece('Later collection piece', ['happy']);
        $earlier = $this->makePiece('Earlier collection piece', ['dreamy']);
        $later->update($metadata + ['collection_number' => 10]);
        $earlier->update($metadata + ['collection_number' => 2]);
        $otherComposer = $this->makePiece('Same title different composer', ['happy']);
        $otherComposer->update($metadata + ['composer_id' => Model::withoutEvents(function () { return create(Composer::class)->id; })]);
        $otherCatalogue = $this->makePiece('Same title different catalogue', ['happy']);
        $otherCatalogue->update(array_replace($metadata, ['catalogue_number' => 99]));

        $response = $this->get(route('webapp.pieces.show', $this->piece))->assertOk();
        $collection = $response->viewData('recommendationRows')->firstWhere('key', 'collection');
        $this->assertSame([$earlier->id, $later->id], $collection['pieces']->pluck('id')->all());
        $xpath = $this->xpath($response->getContent());
        $this->assertSame(2, $xpath->query('//section[@aria-labelledby="piece-collection-heading"]//a[contains(@class,"discover-latest-card")]')->length);
        $this->assertSame(0, $xpath->query('//section[@aria-labelledby="piece-recommendations-heading"]//a[contains(@class,"discover-latest-card")]')->length);
        $this->assertSame(route('webapp.pieces.collection', $this->piece), $collection['url']);
    }

    public function test_named_notebook_can_include_other_composers_but_uncollected_pieces_do_not_form_a_collection()
    {
        $other = $this->makePiece('Another standalone piece', ['happy']);
        $empty = ['collection_name' => null, 'catalogue_name' => null, 'catalogue_number' => null];
        $this->piece->update($empty);
        $other->update($empty);
        $this->assertNull((new PieceRecommendations)->rows($this->piece)->firstWhere('key', 'collection'));
        $this->piece->update(['collection_name' => 'Notebook for Anna Magdalena Bach']);
        $other->update(['collection_name' => 'Notebook for Anna Magdalena Bach', 'composer_id' => Model::withoutEvents(function () { return create(Composer::class)->id; })]);
        $row = (new PieceRecommendations)->rows($this->piece)->firstWhere('key', 'collection');
        $this->assertSame([$other->id], $row['pieces']->pluck('id')->all());
    }

    public function test_missing_categories_are_disabled_but_best_matches_remains_the_default()
    {
        $this->piece->tags()->detach($this->tags->only(['happy', 'dreamy'])->pluck('id'));
        $this->makePiece('Same composer only', [], 'otherLevel', 'otherPeriod');
        $response = $this->get(route('webapp.pieces.show', $this->piece))->assertOk();
        $xpath = $this->xpath($response->getContent());
        $this->assertSame(1, $xpath->query('//button[@id="piece-recommendations-best-matches-tab" and @aria-selected="true" and not(@disabled)]')->length);
        $this->assertSame(3, $xpath->query('//div[@aria-label="More like this"]/button[@disabled]')->length);
        $this->assertSame(1, $xpath->query('//div[@id="piece-recommendations-best-matches"]/p')->length);
    }

    public function test_no_recommendations_omits_both_sections()
    {
        $response = $this->get(route('webapp.pieces.show', $this->piece))->assertOk();
        $xpath = $this->xpath($response->getContent());
        $this->assertSame(0, $xpath->query('//section[@aria-labelledby="piece-collection-heading" or @aria-labelledby="piece-recommendations-heading"]')->length);
    }

    private function xpath($html)
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        return new \DOMXPath($dom);
    }
}
