<?php

namespace Tests\Traits;

trait CustomAssertions
{
	public function assertRedisContains($redisKey, $logKey, $logValue)
	{
        $log = array_map('json_decode', \Redis::hgetall($this->redisPrefix . $redisKey));

        krsort($log);

        $this->assertTrue(isset($log[array_key_first($log)]->data->$logKey), 'This log does not have the key ' . $logKey . '.');
        $this->assertTrue($log[array_key_first($log)]->data->$logKey == $logValue, 'This log does not have the value ' . $logValue . '.');
	}

	public function assertRedisNotContains($redisKey, $logKey, $logValue)
	{
        $log = array_map('json_decode', \Redis::hgetall($this->redisPrefix . $redisKey));

        krsort($log);

        $this->assertFalse(isset($log[array_key_first($log)]->data->$logKey), 'This log has the key ' . $logKey . '.');
        $this->assertFalse($log[array_key_first($log)]->data->$logKey == $logValue, 'This log has the key ' . $logKey . '.');
	}

	public function assertRedisHas($key)
	{
		$exists = (bool) \Redis::exists($this->redisPrefix . $key);

		return $this->assertTrue($exists, 'Failed asserting that the key ' . $key . ' was pushed to Redis.');
	}

	public function assertRedisMissing($key)
	{
		$exists = (bool) \Redis::exists($this->redisPrefix . $key);

		return $this->assertFalse($exists, 'The key ' . $key . ' was pushed to Redis.');
	}

	public function assertRedisEmpty()
	{
		$exists = (bool) \Redis::keys($this->redisPrefix . '*');

		return $this->assertFalse($exists, 'Failed asserting that Redis is empty.');
	}
}
