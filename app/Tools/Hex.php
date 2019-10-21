<?php

namespace App\Tools;

class Hex
{
	protected $symbols = [
		'plus' => [
			'html' => '&#43;',
			'css' => '\002B'
		],
		'minus' => [
			'html' => '&#8722;',
			'css' => '\2212'
		]
	];

	public function get($symbol, $type = 'html')
	{
		if (! array_key_exists($symbol, $this->symbols))
			return null;

		return html_entity_decode($this->symbols[$symbol][$type]);
	}
}
