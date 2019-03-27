<?php

namespace Tests\Unit;

use Tests\AppTest;
use App\Blog\Post;
use App\Admin;

class TopicTest extends AppTest
{
	/** @test */
	public function it_has_many_posts()
	{
		$this->assertInstanceOf(Post::class, $this->topic->posts->first());
	}

	/** @test */
	public function it_belongs_to_an_admin()
	{
		$this->assertInstanceOf(Admin::class, $this->topic->creator);
	}
}
