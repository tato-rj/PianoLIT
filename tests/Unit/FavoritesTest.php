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

		$this->folder = create(FavoriteFolder::class, ['user_id' => $this->favorite->user_id]);

		$this->favorite->folder()->associate($this->folder);
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

    /** @test */
    public function it_knows_how_to_save_a_favorite_into_a_folder()
    {
    	$this->assertTrue($this->piece->isFavorited($this->user->id));
    	
    	$this->assertEquals(0, $this->user->favoriteFolders()->first()->favorites_count);

    	Favorite::toggle($this->user, $this->piece, $this->folder);

    	$this->assertEquals(1, $this->user->favoriteFolders()->first()->favorites_count);
    }

    /** @test */
    public function it_knows_how_to_remove_a_favorite_from_a_folder()
    {
    	$this->assertTrue($this->piece->isFavorited($this->user->id));
    	
    	$this->assertEquals(0, $this->user->favoriteFolders()->first()->favorites_count);

    	Favorite::toggle($this->user, $this->piece, $this->folder);

    	$this->assertEquals(1, $this->user->favoriteFolders()->first()->favorites_count);

    	Favorite::toggle($this->user, $this->piece, $this->folder);

    	$this->assertEquals(0, $this->user->favoriteFolders()->first()->favorites_count);
    }

    /** @test */
    public function it_knows_how_to_move_a_favorite_from_one_folder_to_another()
    {
    	$this->assertTrue($this->piece->isFavorited($this->user->id));
    	
    	$this->assertEquals(1, $this->user->favorites()->count());

    	$this->assertEquals(0, $this->user->favoriteFolders()->first()->favorites_count);

    	Favorite::moveTo($this->user, $this->piece, null, $this->folder);

    	$this->assertEquals(1, $this->user->favoriteFolders()->first()->favorites_count);

    	$this->assertEquals(1, $this->user->favorites()->count());
    }

    /** @test */
    public function it_cannot_move_a_favorite_from_a_folder_where_it_doesnt_exist()
    {
        $this->expectException('Illuminate\Database\Eloquent\ModelNotFoundException');    	

    	Favorite::moveTo($this->user, $this->piece, null, $this->folder);

    	Favorite::moveTo($this->user, $this->piece, null, $this->folder);
    }

    /** @test */
    public function its_folder_knows_how_to_order_its_pieces_by_default()
    {
        $secondPiece = create(Piece::class);

        Favorite::toggle($this->user, $this->piece, $this->folder);
        Favorite::toggle($this->user, $secondPiece, $this->folder);

        \DB::table('favorites')->update(['order' => 0]);

        $this->assertEquals($this->folder->favorites()->get()->pluck('order')->toArray(), [0, 0]);

        $this->folder->sort();

        $this->assertEquals($this->folder->favorites()->get()->pluck('order')->toArray(), [0, 1]);
    }

    /** @test */
    public function its_folder_knows_how_to_order_its_pieces_given_a_different_order()
    {
        $secondPiece = create(Piece::class);

        Favorite::toggle($this->user, $this->piece, $this->folder);
        Favorite::toggle($this->user, $secondPiece, $this->folder);

        $this->assertEquals($this->folder->favorites->first()->piece->id, $this->piece->id);

        $this->folder->sort([$this->folder->favorites[1]->id, $this->folder->favorites[0]->id]);

        $this->assertEquals($this->folder->favorites()->first()->piece->id, $secondPiece->id);
        $this->assertEquals($this->folder->favorites()->get()->pluck('order')->toArray(), [0, 1]);
    }
}
