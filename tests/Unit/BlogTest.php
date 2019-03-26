<?php

namespace Tests\Unit;

use App\Blog\Post;
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
	public function it_knows_how_to_publish_or_unpublish_itself()
	{
		$this->assertFalse($this->post->is_published);

		$this->post->updateStatus();

		$this->assertTrue($this->post->fresh()->is_published);

		$this->post->updateStatus();

		$this->assertFalse($this->post->fresh()->is_published);
	}
}
