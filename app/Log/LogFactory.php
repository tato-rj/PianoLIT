<?php

namespace App\Log;

use Carbon\Carbon;
use App\Log\Loggers\DailyLog;
use Illuminate\Support\Facades\Redis;

class LogFactory
{
	protected $types = ['app', 'web', 'webapp'];

	public function __construct()
	{
		$this->prefix = config('database.redis.prefix');
	}

	public function push(Logger $logger)
	{
		$timestamp = now()->timestamp;
		$key = $this->prefix . $logger->getKey();
		$value = [$timestamp => $logger->getData()];

		Redis::hmset($key, $value);
		(new UserLogIndex)->record($logger->getKey(), $timestamp);

		(new DailyLog)->getDate($timestamp)->increment($key);
	}

	public function get($userId, $type = null)
	{
		$key = $this->prefix . 'user:' . $userId . ':';

		if ($type)
			return $this->redisToArray($key, $type);

		$log = collect();

		foreach ($this->types as $type) {
			$log->$type = $this->redisToArray($key, $type);
		}

		return $log;
	}

	public function count($userId, $type = null)
	{
		if (! $type && (new UserLogIndex)->isReady())
			return (new UserLogIndex)->count($userId);

		$key = $this->prefix . 'user:' . $userId . ':';

		if ($type)
			return count($this->redisToArray($key, $type));
		
		$count = 0;

		foreach ($this->types as $type) {
			$count += count($this->redisToArray($key, $type));
		}

		return $count;
	}

	public function last($userId, $type = null)
	{
		if (! $type && (new UserLogIndex)->isReady()) {
			$timestamp = (new UserLogIndex)->lastActiveTimestamp($userId);

			return $timestamp ? carbon($timestamp) : null;
		}

		$key = $this->prefix . 'user:' . $userId . ':';

		$recent = [];

		foreach ($this->types as $type) {
			$data = $this->redisToArray($key, $type);
			if (! empty($data)) {
				$time = key($data);
				if ($time)
					array_push($recent, $time);		
			}
		}

		if (empty($recent))
			return null;

		rsort($recent);

		return carbon($recent[0]);
	}

	public function redisToArray($key, $type)
	{
		$log = array_map('json_decode', Redis::hgetall($key . $type));

		krsort($log);

		return $log;
	}

	public function total($type = 'user')
	{
		if ($type === 'user' && (new UserLogIndex)->isReady())
			return (new UserLogIndex)->total();

		return count(Redis::keys($type . ':*'));
	}
}
