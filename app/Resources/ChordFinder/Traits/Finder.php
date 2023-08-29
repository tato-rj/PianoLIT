<?php

namespace App\Resources\ChordFinder\Traits;

trait Finder
{
	public function find($notes, $interval)
	{
		foreach ($notes['intervals'] as $note) {
			if (isset($note['interval']) && $note['interval'] == $interval)
				return $note;
		}

		return [
			'interval' => null,
			'steps' => null,
			'name' => null,
			'type' => null,
			'shorthand' => null
		];
	}
}
