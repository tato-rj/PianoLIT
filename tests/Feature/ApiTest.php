<?php

namespace Tests\Feature;

use App\Piece;
use Tests\AppTest;

class ApiTest extends AppTest
{
    /** @test */
    public function the_number_of_results_for_any_search_can_be_fetched()
    {
        Piece::truncate();

        create(Piece::class, ['name' => 'Sonata']);
        create(Piece::class, ['name' => 'Minuet 1']);
        create(Piece::class, ['name' => 'Minuet 2']);

        $this->get(route('api.search', ['search' => 'minuet', 'count', 'global']))
             ->assertJson(['count' => 2]);
    }
}
