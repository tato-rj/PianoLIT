<?php

namespace Tests\Unit;

use App\{Admin, Composer, Country, Tag};
use Tests\Traits\ManageDatabase;
use Tests\AppTest;

class PieceTest extends AppTest
{
    use ManageDatabase;

	/** @test */
	public function it_belonds_to_an_admin()
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
    public function audio_files_and_score_are_uploaded_when_a_piece_is_created()
    {
        \Storage::fake('public');

        $this->signIn();

        $piece = $this->postPiece();

        \Storage::disk('public')->assertExists($piece->fresh()->audio_path);
        \Storage::disk('public')->assertExists($piece->fresh()->audio_path_rh);
        \Storage::disk('public')->assertExists($piece->fresh()->audio_path_lh);
        \Storage::disk('public')->assertExists($piece->fresh()->score_path);
    }
}
