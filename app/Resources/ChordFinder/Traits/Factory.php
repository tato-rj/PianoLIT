<?php

namespace App\Resources\ChordFinder\Traits;

trait Factory
{
	protected $whiteEnharmonics = [
		'e+' => 'f',
		'f-' => 'e',
		'b+' => 'c',
		'c-' => 'b'
	];
}