<?php

namespace Tests\Feature;

use App\{Piece, Tag};
use Tests\AppTest;

class SearchTest extends AppTest
{
    /** @test */
    public function users_can_perform_searches_by_keywords()
    {
    	Tag::truncate();
        $this->piece->tags()->detach();

        $this->piece->tags()->attach(create(Tag::class, ['name' => 'foo']));

        $this->assertCount(0, Piece::search(['xxx'])->get());
        $this->assertCount(1, Piece::search(['foo'])->get());
    }
}
