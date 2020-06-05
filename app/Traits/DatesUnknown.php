<?php

namespace App\Traits;

use App\Composer;

trait DatesUnknown
{
	protected $unknownBirthdays = [
		Composer::class => [128 => 'c. 1729']
	];

	protected $unknownDeathdays = [];

	public function unknownBirthday()
	{
		if (! array_key_exists(get_class($this), $this->unknownBirthdays))
			return false;

		if (! array_key_exists($this->id, $this->unknownBirthdays[get_class($this)]))
			return false;

		return $this->unknownBirthdays[get_class($this)][$this->id];
	}

	public function unknownDeathday()
	{
		if (! array_key_exists(get_class($this), $this->unknownDeathdays))
			return false;

		if (! array_key_exists($this->id, $this->unknownDeathdays[get_class($this)]))
			return false;

		return $this->unknownDeathdays[get_class($this)][$this->id];
	}
}
