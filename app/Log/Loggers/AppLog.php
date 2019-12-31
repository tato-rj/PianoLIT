<?php

namespace App\Log\Loggers;

use App\Log\Logger;

class AppLog extends Logger
{
	public function getKey()
	{
		return 'user:' . request()->user_id . ':app';
	}

	public function getData()
	{
		return json_encode(['url' => url()->current(), 'data' => request()->except(['user_id', '_method', '_token'])]);		
	}
}