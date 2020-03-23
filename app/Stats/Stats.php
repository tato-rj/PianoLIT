<?php

namespace App\Stats;

use App\Stats\Factories\{UserStats, PieceStats, SubscriptionStats};

class Stats {
	protected $factory;
	protected $factories = [
		'users' => UserStats::class,
		'subscriptions' => SubscriptionStats::class,
		'pieces' => PieceStats::class,
	];

	public function for($table)
	{
		$this->factory = new $this->factories[$table];

		return $this;
	}

	public function query(string $method, $args = null)
	{
		if (method_exists($this->factory, $method))
			return $this->factory->$method($args);

		throw new \Exception('The method ' . $method . ' does not exist.', 404);
	}
}
