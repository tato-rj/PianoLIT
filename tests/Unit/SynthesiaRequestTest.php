<?php

namespace Tests\Unit;

use Tests\AppTest;
use App\{Piece, User, SynthesiaRequest};

class SynthesiaRequestTest extends AppTest
{
    public function setUp() : void
    {
        parent::setUp();
		
		$this->synthesiaRequest = create(SynthesiaRequest::class);
    }

	/** @test */
	public function it_belongs_to_a_user()
	{
		$this->assertInstanceOf(User::class, $this->synthesiaRequest->user);
	}
	
	/** @test */
	public function it_belongs_to_a_piece()
	{
		$this->assertInstanceOf(Piece::class, $this->synthesiaRequest->piece);
	}
}
