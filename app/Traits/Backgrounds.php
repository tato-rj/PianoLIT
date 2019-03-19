<?php

namespace App\Traits;

trait Backgrounds
{
	protected $colors = [
		'blue' => '#0396FFFF',
		'green' => '#28C76FFF',
		'teal' => '#00C9B7FF',
		'purple' => '#7367F0FF',
		'pink' => '#EB5286FF',
		'orange' => '#F67A36FF'
	];

	public function getColor($color)
	{
		if (array_key_exists($color, $this->colors))
			return $this->colors[$color];

		return '#000000';
	}

	public function getBackground($color)
	{
		$number = mt_rand(1,5);

		return asset("pianolit/images/backgrounds/{$color}/{$number}.png");
	}
}