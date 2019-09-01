<?php

namespace Tests\Unit;

use App\{Admin, Composer, Country, Tag, Playlist, Piece, PieceView};
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
	public function it_has_many_views()
	{
		$this->assertInstanceOf(PieceView::class, $this->piece->views->first());		 
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

    /** @test */
    public function it_knows_the_status_of_its_resources()
    {

        $completePiece = create(Piece::class, [
            'score_path' => 'score.pdf', 
            'audio_path' => 'audio.mp3',
            'videos' => serialize(['videos']),
            'itunes' => serialize(['itunes'])]);

        $completePiece->tags()->attach($this->tag);

    	$this->assertFalse($incompletePiece->hasScore());
    	$this->assertTrue($completePiece->hasScore());

    	$this->assertFalse($incompletePiece->hasAudio());
    	$this->assertTrue($completePiece->hasAudio());

    	$this->assertFalse($incompletePiece->hasTags());
    	$this->assertTrue($completePiece->hasTags());    	 

    	$this->assertFalse($incompletePiece->hasITunes());
    	$this->assertTrue($completePiece->hasITunes());    	 

    	$this->assertFalse($incompletePiece->hasVideos());
    	$this->assertTrue($completePiece->hasVideos());    	 

    	$this->assertFalse($incompletePiece->isComplete());
    	$this->assertTrue($completePiece->isComplete());    	 
    }

    /** @test */
    public function it_knows_the_other_pieces_from_its_collection()
    {
        $piece = create(Piece::class);
        $differentPiece = create(Piece::class);
        $pieceFromSameCatalogue = create(Piece::class, [
        	'composer_id' => $piece->composer_id,
        	'catalogue_name' => $piece->catalogue_name,
        	'catalogue_number' => $piece->catalogue_number
        ]);

        $this->assertCount(1, $piece->siblings());
        $this->assertEquals($piece->siblings()->first()->id, $pieceFromSameCatalogue->id);
    }

    /** @test */
    public function it_finds_other_pieces_like_it()
    {
        $baroque = create(Tag::class, ['type' => 'period', 'name' => 'baroque']);
        $modern = create(Tag::class, ['type' => 'period', 'name' => 'modern']);

        $beginner = create(Tag::class, ['type' => 'level', 'name' => 'beginner']);
        $advanced = create(Tag::class, ['type' => 'level', 'name' => 'advanced']);

        $happy = create(Tag::class, ['type' => 'mood', 'name' => 'happy']);
        $meditative = create(Tag::class, ['type' => 'mood', 'name' => 'meditative']);
        $serious = create(Tag::class, ['type' => 'mood', 'name' => 'serious']);

        $piece = create(Piece::class);
        $piece->tags()->attach([$baroque->id, $beginner->id, $happy->id]);
        $pieceSimilar = create(Piece::class);
        $pieceSimilar->tags()->attach([$baroque->id, $beginner->id, $happy->id]);
        $pieceNotSimilar = create(Piece::class);
        $pieceNotSimilar->tags()->attach([$modern->id, $advanced->id, $meditative->id]);
        $pieceAlsoNotSimilar = create(Piece::class);
        $pieceAlsoNotSimilar->tags()->attach([$baroque->id, $advanced->id, $meditative->id]);

        $this->assertCount(1, $piece->similar());
        $this->assertEquals($piece->similar()->first()->id, $pieceSimilar->id);
    }
}
