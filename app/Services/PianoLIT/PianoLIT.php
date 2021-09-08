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

	public function appToWebapp($url)
	{
        $url = substr_replace($url, 'my.', 8, 0);
        return str_replace('api/', '', $url);
	}
}