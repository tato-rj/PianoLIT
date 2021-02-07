<?php

namespace App\Exports\Subscriptions;

use App\Exports\Subscriptions\Factories\{Members, Fans};

class SubscriptionExport
{
	protected $factory;

	protected $types = [
		'members' => Members::class,
		'fans' => Fans::class
	];

	public function for($type)
	{
		if (! array_key_exists($type, $this->types))
			throw new \Exception('We can\'t export ' . $type . ' from the subscriptions table.', 404);
		
		$this->factory = new $this->types[$type];

		return $this;		
	}

	public function get()
	{
		return $this->factory->generate();
	}
}
