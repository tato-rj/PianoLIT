<?php

namespace App\Resources\Technique;

use App\Resources\Technique\Traits\Keys;

class Validator
{
	use Keys;

	public function check($key)
	{
		$key = ucfirst($key);

		if (is_null($key))
			abort(422, 'You forgot to give us the key');

		if (! array_key_exists($key, $this->keys))
			abort(422, 'We don\'t have information for this key');		
	}
}
