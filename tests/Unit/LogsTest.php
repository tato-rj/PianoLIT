<?php

namespace Tests\Unit;

use Tests\AppTest;

class LogsTest extends AppTest
{
	/** @test */
	public function it_only_records_not_null_request_values()
	{
        $this->signIn($this->user);

        $this->assertRedisEmpty();

        $this->get(route('home', ['ignore', 'foo' => 'bar']));

        $key = 'user:'.$this->user->id.':web';

        $this->assertRedisContains($key, 'foo', 'bar');
        // NEEDS IMPROVEMENT TO CHECK IF THE IGNORE KEY IS ACTUALLY MISSING
	}
}
