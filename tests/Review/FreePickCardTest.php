<?php

namespace Tests\Review;

use App\{Composer, Piece};
use App\Api\Api;

class FreePickCardTest extends ReviewTestCase
{
    public function test_feature_title_omits_feed_catalogue_and_movement_numbers()
    {
        foreach ([
            [null, null, 'Prelude'],
            ['Song of the madwoman on the seashore', null, 'Song of the madwoman on the seashore'],
            ['Sonata nickname', 'Sonata', 'Prelude'],
        ] as [$nickname, $collection, $expected]) {
            $piece = (new Piece)->forceFill([
                'id' => 1, 'slug' => 'test-piece', 'name' => 'Prelude',
                'nickname' => $nickname, 'collection_name' => $collection,
                'catalogue_name' => 'Op.', 'catalogue_number' => '31',
                'collection_number' => '8', 'movement_number' => '2',
                'cover_path' => 'fixture.jpg', 'webapp_has_synthesia' => false,
            ])->syncOriginal();
            $piece->setRelation('composer', (new Composer)->forceFill(['name' => 'Charles Valentin Alkan']));
            $piece->setRelation('tags', collect());
            (new Api)->withAttributes([$piece], ['source' => '/fixture']);
            $this->assertStringContainsString('Op.31 No.8', $piece->name);

            $html = view('webapp.components.piece.highlight', ['piece' => $piece, 'freePick' => true])->render();
            $dom = new \DOMDocument;
            @$dom->loadHTML($html);
            $xpath = new \DOMXPath($dom);
            $this->assertSame($expected, $xpath->query('//h4')->item(0)->textContent);
            $this->assertStringContainsString('Op.31 No.8', $xpath->query('//p[contains(@class, "free-pick-card__metadata")]')->item(0)->textContent);
        }
    }
}
