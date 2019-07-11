<?php

namespace App\Resources\CircleOfFifths;

class Enharmonics
{
	protected $map = [
		'c' => ['b#', 'dbb'],
		'c#' => ['db'],
		'd' => ['ebb', 'c##'],
		'd#' => ['eb'],
		'e' => ['d##', 'fb'],
		'f' => ['e#', 'gbb'],
		'f#' => ['gb'],
		'g' => ['f##', 'abb'],
		'g#' => ['ab'],
		'a' => ['g##', 'bbb'],
		'a#' => ['bb'],
		'b' => ['a##', 'cb']
	];

	public function find($input, $includeOriginal = true)
	{
		foreach ($this->map as $note => $enharmonics) {
			if ($input == $note) {
				if ($includeOriginal)
					array_push($enharmonics, $note);

				return $enharmonics;
			}

			if (in_array($input, $enharmonics)) {
				if (! $includeOriginal) {
					$index = array_search($input, $enharmonics);
					unset($enharmonics[$index]);
				}
				array_push($enharmonics, $note);
				
				return $enharmonics;
			}
		}

		return false;
	}
}
