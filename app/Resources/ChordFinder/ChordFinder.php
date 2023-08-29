<?php

namespace App\Resources\ChordFinder;

use Illuminate\Http\Request;

class ChordFinder
{
	protected $notes, $results, $root, $bass, $tool;

	public function cleaner()
	{
		return new Cleaner($this->notes);
	}

	public function inversion($chord)
	{
		return new Inversion($chord);
	}

	public function validator()
	{
		return new Validator($this->results);
	}

	public function label()
	{
		return new Label($this->results);
	}

	public function ranking()
	{
		return new Ranking($this->results, $this->root);
	}

	public function tool($tool)
	{
		$this->tool = $tool;

		return $this;
	}

	public function root($root)
	{
		$this->root = $root;

		if ($this->root)
			$this->bass = str_replace('s', '+', $this->notes[0]);

		return $this;
	}

	public function take(Request $request)
	{
		$this->notes = $request->notes;
		$this->root($request->root);
		$this->tool($request->tool);

		return $this;
	}

	public function debug()
	{
		$query = '?';

		foreach ($this->notes as $note) {
			$note = str_replace('+', 's', $note);
			$query .= 'notes[]=' . $note . '&';	
		}

		$query .= "root={$this->root}&";

		$query .= "tool={$this->tool}&";

		$query .= 'dev';

		return route('tools.chord-finder.analyse') . $query;
	}

	public function validate()
	{
		$this->notes = $this->cleaner()
							->setMinimum(3)
							->lowercase()
							->removeDuplicates()
							->fixSharps()
							->sort($this->root, $this->tool)
							->getNotes();

		return $this;
	}

	public function analyse()
	{
			$this->getInversions();

			$this->results = $this->label()->intervals();

			// if ($this->root) {
			// 	$this->results = $this->label()->strict($this->bass, $this->tool)->chords();
			// } else {
				$this->results = $this->validator()->addNinth()->get();

				$this->results = $this->validator()->addEleventh()->get();

				$this->results = $this->validator()->addThirteenth()->get();
				// $this->results = $this->validator()->fixAddedIntervals()->get();
				$this->results = $this->label()->strict($this->bass, $this->tool)->chords();
				
			// }

			$this->results = $this->validator()->removeImpossible()->get();

			$this->results = array_values($this->results);

		return $this;
	}

	public function ranked()
	{
		$this->results = $this->ranking()->apply();

		return $this;
	}

	public function get()
	{		
		$this->results['tool'] = $this->tool;

		return $this->results;
	}

	public function getInversions()
	{
		$results = [];

		if ($this->root) {
			$results[0]['notes'] = $this->notes[0];
			$results[0]['inversions'] = $this->inversion($this->notes[0])->defined($this->root);
		} else {
			foreach ($this->notes as $index => $notes) {
				$results[$index]['notes'] = $notes;
				$results[$index]['inversions'] = $this->inversion($notes)->all();
			}			
		}
		
		$this->results = $results;
	}
}
