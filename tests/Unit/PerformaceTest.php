<?php

namespace Tests\Unit;

use Tests\AppTest;
use App\{Performance, User, Piece, Clap};

class PerformanceTest extends AppTest
{
    /** @test */
    public function it_belongs_to_a_user()
    {
        $performance = create(Performance::class, ['user_id' => $this->user]);

        $this->assertInstanceOf(User::class, $performance->user);
    }

    /** @test */
    public function it_belongs_to_a_piece()
    {
        $performance = create(Performance::class, ['piece_id' => $this->piece]);

        $this->assertInstanceOf(Piece::class, $performance->piece);
    }

    /** @test */
    public function it_has_many_claps()
    {
        $performance = create(Performance::class, ['piece_id' => $this->piece]);

        create(Clap::class, ['performance_id' => $performance]);

        $this->assertInstanceOf(Clap::class, $performance->claps()->first());
    }
}
