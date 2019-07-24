<?php

namespace App\Resources\ChordFinder;

class Validator
{
	protected $array;

	public function __construct(array $array)
	{
		$this->array = $array;
	}

	public function removeImpossible()
	{
		$copy = $this->array;

		foreach ($this->array as $index => $result) {
			foreach ($result['inversions'] as $key => $inversion) {
				if (in_array(null, $inversion['intervals']))
					unset($copy[$index]['inversions'][$key]);
			}
		}

		foreach ($copy as $index => $result) {
			if (empty($result['inversions'])) {
				unset($copy[$index]);
			} else {
				$copy[$index]['inversions'] = array_values($result['inversions']);
			}
		}

		dd($copy);
	}
}
