<?php

namespace Tests\Unit;

use App\{Admin, Composer, Country, Tag, Playlist, Piece};
use Tests\Traits\ManageDatabase;
use Tests\AppTest;

class PieceTest extends AppTest
{
    use ManageDatabase;

	/** @test */
	public function it_belongs_to_an_admin()
	{
		$this->assertInstanceOf(Admin::class, $this->piece->creator);
	}

	/** @test */
	public function it_belongs_to_a_composer()
	{
		$this->assertInstanceOf(Composer::class, $this->piece->composer);
	}

	/** @test */
	public function it_knows_its_nationality()
	{
		$this->assertInstanceOf(Country::class, $this->piece->country);
	}

	/** @test */
	public function it_has_many_tags()
	{
		$this->assertInstanceOf(Tag::class, $this->piece->tags()->first());
	}

	/** @test */
	public function it_has_many_playlists()
	{
		$this->assertInstanceOf(Playlist::class, $this->piece->playlists()->first());
	}

	/** @test */
	public function it_has_a_level_a_length_and_a_period()
	{
		$level = create(Tag::class, ['type' => 'level']);
		$length = create(Tag::class, ['type' => 'length']);
		$period = create(Tag::class, ['type' => 'period']);

		$this->piece->tags()->attach($level);
		$this->piece->tags()->attach($length);
		$this->piece->tags()->attach($period);

		$this->assertNotNull($this->piece->level);
		$this->assertNotNull($this->piece->length);
		$this->assertNotNull($this->piece->period);
	}

    /** @test */
    public function it_knows_if_it_has_been_favorited_by_a_given_user()
    {
    	$this->assertTrue($this->piece->isFavorited($this->user->id));

    	$this->assertFalse(create(Piece::class)->isFavorited($this->user->id));
    }
}
