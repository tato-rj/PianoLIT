<?php

namespace App\Resources\ChordFinderOLD;

class ChordFinder
{
	use Factory;

	public $notes, $full_name, $short_name, $chord, $results, $enharmonicResults, $inversions, $enharmonicInversions,$enharmonicNotes;

	public function __construct()
	{
		$this->results = ['main_content' => [], 'additional_content' => []];
		$this->enharmonicResults = ['main_content' => [], 'additional_content' => []];
		$this->enharmonicInversions = [];
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

			$type = 'intervals';
		} else {
			foreach ($this->inversions as $inversion) {
				$chord = $this->worker()->get($inversion);
				
				if ($chord) {
					if ($chord['is_relevant']) {
						if ($chord['is_main']) {
							array_push($this->results['main_content'], $chord);
						} else {
							array_push($this->results['additional_content'], $chord);
						}
					}
				}
			}

			if ($this->enharmonicNotes) {
				foreach ($this->enharmonicInversions as $inversion) {
					$chord = $this->worker()->get($inversion);
					
					if ($chord['is_relevant']) {
						if ($chord['is_main']) {
							array_push($this->enharmonicResults['main_content'], $chord);
						} else {
							array_push($this->enharmonicResults['additional_content'], $chord);
						}
					}
				}
			}

			$type = 'chords';
		}

		foreach ($this->notes as $index => $note) {
			$this->notes[$index] = ucfirst(strtr($this->notes[$index], ['+' => '#', '-' => 'b']));
		}	

		if ($this->enharmonicNotes) {
			foreach ($this->enharmonicNotes as $index => $note) {
				$this->enharmonicNotes[$index] = ucfirst(strtr($this->enharmonicNotes[$index], ['+' => '#', '-' => 'b']));
			}
		}

		return [
			'type' => $type,
			'notes' => $this->notes,
			'enharmonic' => $this->enharmonicNotes,
			'results' => $this->results,
			'enharmonicResults' => $this->enharmonicResults
		];
	}
}
