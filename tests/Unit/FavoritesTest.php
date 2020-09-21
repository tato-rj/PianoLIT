<?php

namespace Tests\Unit;

use App\{User, Piece, Favorite, FavoriteFolder};
use Tests\AppTest;

class FavoritesTest extends AppTest
{
    public function setUp() : void
    {
        parent::setUp();

		$this->favorite = Favorite::first();
		
		$folder = create(FavoriteFolder::class, ['user_id' => $this->favorite->user_id]);

		$this->favorite->folder()->associate($folder);
    }

	/** @test */
	public function it_belongs_to_a_user()
	{
		$this->assertInstanceOf(User::class, $this->favorite->user);
	}

	/** @test */
	public function it_belongs_to_a_piece()
	{
		$this->assertInstanceOf(Piece::class, $this->favorite->piece);
	}

	/** @test */
	public function it_belongs_to_a_folder()
	{
		$this->assertInstanceOf(FavoriteFolder::class, $this->favorite->folder);
	}

    /** @test */
    public function it_knows_if_it_has_been_favorited_by_a_given_user()
    {
    	$this->assertTrue($this->piece->isFavorited($this->user->id));

    	$this->assertFalse(create(Piece::class)->isFavorited($this->user->id));
    }
}
