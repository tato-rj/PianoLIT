<?php

namespace Tests\Traits;

use Carbon\Carbon;

trait ManageTime
{
	public function setDate($date)
	{
		\Carbon\Carbon::setTestNow($date);

		return now();
	}

	public function resetDate()
	{
		\Carbon\Carbon::setTestNow(now());

		return now();		
	}
}
