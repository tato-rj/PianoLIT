<?php

namespace App\Services\PianoLIT;

class PianoLIT
{
	public function email($type = 'general')
	{
		if (isset(config('app')['emails'][$type]))
			return config('app')['emails'][$type];

		return null;
	}
}