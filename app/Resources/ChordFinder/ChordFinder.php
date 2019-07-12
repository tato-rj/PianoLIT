<?php

namespace App\Resources\ChordFinder;

class ChordFinder
{
	use Factory;

	public $notes, $full_name, $short_name, $chord, $results, $inversions;

	public function __construct()
	{
		$this->results = ['main_content' => [], 'additional_content' => [], 'type' => ''];
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
		if (count($this->notes) == 2) {
			array_push($this->results['main_content'], $this->worker()->interval($this->notes[0], $this->notes[1])->analyse());
			array_push($this->results['main_content'], $this->worker()->interval($this->notes[1], $this->notes[0])->analyse());

			$this->results['type'] = 'intervals';
		} else {
			foreach ($this->inversions as $inversion) {
				$chord = $this->worker()->get($inversion);
				
				if ($chord['is_relevant']) {
					if ($chord['is_main']) {
						array_push($this->results['main_content'], $chord);
					} else {
						array_push($this->results['additional_content'], $chord);
					}
				}
			}

			$this->results['type'] = 'chords';
		}
		
		foreach ($this->notes as $index => $note) {
			$this->notes[$index] = ucfirst(strtr($note, ['+' => '#', '-' => 'b']));
		}

		return [
			'notes' => $this->notes,
			'results' => $this->results
		];
	}
}
