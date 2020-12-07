<?php

namespace Tests\Unit;

use App\Shop\{eScore, eScoreTopic};
use App\Merchandise\Purchase;
use App\Admin;
use Tests\AppTest;
use Illuminate\Http\UploadedFile;

class eScoresTest extends AppTest
{
	/** @test */
	public function it_has_many_topics()
	{
		$this->assertInstanceOf(eScoreTopic::class, $this->escore->topics->first());
	}

	/** @test */
	public function it_belongs_to_an_admin()
	{
		$this->assertInstanceOf(Admin::class, $this->escore->creator);
	}

	/** @test */
	public function it_has_many_pieces()
	{
		$this->assertInstanceOf(Piece::class, $this->escore->pieces->first());
	}

	/** @test */
	public function it_has_many_purchases()
	{
		$this->signIn();
		
		$this->user->purchase($this->escore);
		
		$this->assertInstanceOf(Purchase::class, $this->escore->purchases->first());
	}

	/** @test */
	public function it_knows_how_to_upload_a_preview_page()
	{
		\Storage::fake('public');

		$image1 = UploadedFile::fake()->image('preview.jpg');
		$image2 = UploadedFile::fake()->image('preview.jpg');

		$this->assertEmpty($this->escore->previews);

		$this->escore->savePreview($image1);

		$this->assertCount(1, $this->escore->previews);

		$this->escore->savePreview($image2);

		$this->assertCount(2, $this->escore->previews);
	}

	/** @test */
	public function it_knows_how_to_delete_a_preview_page()
	{
		\Storage::fake('public');

		$image1 = UploadedFile::fake()->image('preview.jpg');
		$image2 = UploadedFile::fake()->image('preview.png');

		$this->escore->savePreview($image1);
		$this->escore->savePreview($image2);
		
		$this->assertEquals(2, $this->escore->previews_count);

		$this->escore->deletePreview($this->escore->previews[0]);

		$this->assertEquals(1, $this->escore->previews_count);
	}
}
