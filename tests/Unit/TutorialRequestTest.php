<?php

namespace Tests\Unit;

use Tests\AppTest;
use App\{Piece, User, TutorialRequest};

class TutorialRequestTest extends AppTest
{
    public function setUp() : void
    {
        parent::setUp();
		
		$this->tutorialRequest = create(TutorialRequest::class);
    }

	/** @test */
	public function it_belongs_to_a_user()
	{
		$this->assertInstanceOf(User::class, $this->tutorialRequest->user);
	}
	
	/** @test */
	public function it_belongs_to_a_piece()
	{
		$this->assertInstanceOf(Piece::class, $this->tutorialRequest->piece);
	}
}
