<?php

namespace App\Traits;

use App\Log\LogFactory;

trait Loggable
{
	public function redisKey(string $type)
	{
		return config('database.redis.prefix') . 'user:' . $this->id . ':' . $type;	
	}

	public function log($type = null)
	{
		return (new LogFactory)->get($this->id, $type);
	}

	public function lastActive($type = null)
	{
		return (new LogFactory)->last($this->id, $type);		
	}

	public function getLastActiveAtAttribute()
	{
		return (new LogFactory)->last($this->id);
	}

	public function getLogsCountAttribute()
	{
		return (new LogFactory)->count($this->id);
	}

	public function isTopUser($total, $userCount = null)
	{
		$count = $userCount ?? $this->logs_count;

		if ($count == 0)
			return 0;

		return ($count * 100 / $total) >= 20 ? $count : 0;
	}
}
