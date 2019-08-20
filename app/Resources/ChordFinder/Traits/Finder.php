<?php

namespace App\Resources\ChordFinder\Traits;

trait Finder
{
	public function find($notes, $interval)
	{
		foreach ($notes['intervals'] as $note) {
			if ($note['interval'] == $interval)
				return $note;
		}
	}
}
