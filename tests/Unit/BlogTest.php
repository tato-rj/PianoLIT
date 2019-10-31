<?php

namespace Tests\Unit;

use App\Blog\{Post, Topic};
use App\Admin;
use Tests\AppTest;

class BlogTest extends AppTest
{
	/** @test */
	public function a_post_belongs_to_an_admin()
	{
		$this->assertInstanceOf(Admin::class, $this->post->creator);
	}

	/** @test */
	public function it_has_many_topics()
	{
		$this->assertInstanceOf(Topic::class, $this->post->topics->first());
	}

	/** @test */
	public function it_knows_how_to_publish_or_unpublish_itself()
	{
		$this->assertNull($this->post->published_at);

		$this->post->updateStatus();

		$this->assertNotNull($this->post->fresh()->published_at);

		$this->post->updateStatus();

		$this->assertNull($this->post->fresh()->published_at);
	}

	/** @test */
	public function it_knows_how_to_calculate_its_reading_time()
	{
		$this->assertTrue(calculateReadingTime($this->post->content) > 0);
	}
}
