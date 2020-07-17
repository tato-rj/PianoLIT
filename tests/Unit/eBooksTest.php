<?php

namespace Tests\Unit;

use App\Shop\{eBook, eBookTopic};
use App\Merchandise\Purchase;
use App\Admin;
use Tests\AppTest;
use Illuminate\Http\UploadedFile;

class eBooksTest extends AppTest
{
	/** @test */
	public function it_has_many_topics()
	{
		$this->assertInstanceOf(eBookTopic::class, $this->ebook->topics->first());
	}

	/** @test */
	public function it_belongs_to_an_admin()
	{
		$this->assertInstanceOf(Admin::class, $this->ebook->creator);
	}

	/** @test */
	public function it_has_many_purchases()
	{
		$this->signIn();
		
		$this->user->purchase($this->ebook);
		
		$this->assertInstanceOf(Purchase::class, $this->ebook->purchases->first());
	}

	/** @test */
	public function it_knows_how_to_upload_a_preview_page()
	{
		\Storage::fake('public');

		$image1 = UploadedFile::fake()->image('preview.jpg');
		$image2 = UploadedFile::fake()->image('preview.jpg');

		$this->assertEmpty($this->ebook->previews);

		$this->ebook->savePreview($image1);

		$this->assertCount(1, $this->ebook->previews);

		$this->ebook->savePreview($image2);

		$this->assertCount(2, $this->ebook->previews);
	}

	/** @test */
	public function it_knows_how_to_delete_a_preview_page()
	{
		\Storage::fake('public');

		$image1 = UploadedFile::fake()->image('preview.jpg');
		$image2 = UploadedFile::fake()->image('preview.png');

		$this->ebook->savePreview($image1);
		$this->ebook->savePreview($image2);
		
		$this->assertEquals(2, $this->ebook->previews_count);

		$this->ebook->deletePreview($this->ebook->previews[0]);

		$this->assertEquals(1, $this->ebook->previews_count);
	}
}
