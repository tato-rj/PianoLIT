<?php

namespace App\Log\Loggers;

use App\Log\Logger;

class WebLog extends Logger
{
	public function getKey()
	{
		return 'user:' . auth()->guard('web')->user()->id . ':web';
	}

	public function getData()
	{
		return json_encode(['url' => url()->current(), 'data' => []]);	
	}
}