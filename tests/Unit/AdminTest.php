<?php

namespace Tests\Unit;

use App\Blog\Post;
use App\{Piece, Composer, Tag};
use Tests\AppTest;

class AdminTest extends AppTest
{
	/** @test */
	public function it_has_many_pieces()
	{
		$this->assertInstanceOf(Piece::class, $this->admin->pieces()->first());
	}

	/** @test */
	public function it_has_many_blog_posts()
	{
		$this->assertInstanceOf(Post::class, $this->admin->posts()->first());
	}

	/** @test */
	public function it_has_many_composers()
	{
		$this->assertInstanceOf(Composer::class, $this->admin->composers()->first());
	}
	
	/** @test */
	public function it_has_many_tags()
	{
		$this->assertInstanceOf(Tag::class, $this->admin->tags()->first());
	}
}
