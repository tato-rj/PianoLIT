<?php

namespace App\Log\Loggers;

use App\Log\Logger;

class WebAppLog extends Logger
{
	public function getKey()
	{
		return 'user:' . auth()->guard('web')->user()->id . ':webapp';
	}

	public function getData()
	{
		return json_encode(['url' => url()->current(), 'data' => $this->cleanRequest(['_method', '_token'])]);	
	}
}