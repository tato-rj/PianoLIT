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
}
