<?php

namespace App\Services;

class Youtube
{
	protected $highlight = '4V5OoQ8OLjY';
	protected $favorites = ['RA_oZiqinFs', '78QlBf04SXs', 'CV17BV-GNvw', 'ucFvIzoBEeE', 'o6QzqCFTfDM', 'InZvYHx5Huw', '0VG-x-qlfUw', 'Sql0OJu0vwY', 'EKeESVEBhqY'];

	public function favorites($number)
	{
		$codes = $this->favorites;

		shuffle($codes);
		
		return array_unshift(array_slice($codes, 0, $number), $this->highlight);
	}
}
