<?php

namespace App\Resources\ChordFinder;

class ChordFinder
{
	use Factory;

	public $notes, $full_name, $short_name, $chord, $chords, $inversions;

	public function __construct()
	{
		$this->chords = [];
		$this->inversions = [];
		$this->validator = new Validator($this);
		$this->organizer = new Organizer($this);
	}

	public function worker()
	{
		return new Worker($this);
	}

	public function take($notes)
	{
		$this->chord = [];

		$this->notes = $notes;

		$this->validator->run();

		$this->organizer->clean()->run();

		return $this;		
	}

	public function analyse()
	{
		foreach ($this->inversions as $inversion) {
			$chord = $this->worker()->get($inversion);
			
			if ($chord['is_relevant'])
				array_push($this->chords, $chord);
		}

		return [
			'notes' => $this->notes,
			'chords' => $this->chords
		];
	}
}
