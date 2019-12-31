<?php

namespace App\Log;

class LogFactory
{
	protected $types = ['app', 'web'];

	public function __construct()
	{
		$this->prefix = config('database.redis.prefix');
	}

	public function push(Logger $logger)
	{
		$key = $this->prefix . $logger->getKey();
		$value = [now()->timestamp => $logger->getData()];

		\Redis::hmset($key, $value);
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

	public function redisToArray($key, $type)
	{
		$log = array_map('json_decode', \Redis::hgetall($key . $type));

		krsort($log);

		return $log;
	}
}