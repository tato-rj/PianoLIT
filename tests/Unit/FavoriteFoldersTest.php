<?php

namespace Tests\Unit;

use App\{User, Piece, Favorite, FavoriteFolder};
use Tests\AppTest;

class FavoriteFoldersTest extends AppTest
{
    public function setUp() : void
    {
        parent::setUp();

		$this->folder = create(FavoriteFolder::class);
		create(Favorite::class, ['user_id' => $this->folder->user->id, 'favorite_folder_id' => $this->folder->id]);
    }

	/** @test */
	public function it_has_many_favorites()
	{
		$this->assertInstanceOf(Piece::class, $this->folder->favorites->first()->piece);
	}

	/** @test */
	public function it_belongs_to_a_user()
	{
		$this->assertInstanceOf(User::class, $this->folder->user);		 
	}
}
