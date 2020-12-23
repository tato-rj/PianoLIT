<?php

namespace App\Resources\FindYourMatch\Categories;

use App\Tag;

class Level extends Category
{
	public function setPriority()
	{
		$duplicates = $this->query->duplicates()->values();

		if ($duplicates->isEmpty() && $this->query->count() > 1) {
			$level = $this->getTopLevel();
		} else {
			$level = $duplicates;
		}

		$this->and = $level;
	}

	public function getTopLevel()
	{
		$levels = Tag::extendedLevels()->get();
		
		$ordered = collect();

		$levels->map(function($item, $key) use ($ordered) {
			if ($this->query->contains($item->name))
				$ordered->prepend($item->name);
		});

		return $ordered->shift();
	}
}
