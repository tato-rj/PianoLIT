<?php

namespace App\Analytics;

use App\Analytics\Factories\{UserAnalytics, PieceAnalytics};

class Analytics {
	protected $factory;
	protected $factories = [
		'users' => UserAnalytics::class,
		'pieces' => PieceAnalytics::class,
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
