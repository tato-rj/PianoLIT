<?php

namespace App\Resources\ChordFinder;

use App\Resources\ChordFinder\Traits\Notation;

class Label
{
	use Notation;

	protected $array, $bass;

	public function __construct(array $array)
	{
		$this->array = $array;	
	}

	public function analyser($chord)
	{
		return new Analyser($chord);
	}

	public function strict($bass, $tool)
	{
		if ($tool != 'button')
			$this->bass = $bass;
	
		return $this;
	}

	public function intervals()
	{
		foreach ($this->array as $index => $chord) {
			foreach ($chord['inversions'] as $key => $inversion) {
				$this->array[$index]['inversions'][$key]['intervals'] = $this->analyser($inversion['chord'])->intervals();
			}
		}

		return $this->array;
	}

	public function chords()
	{
		foreach ($this->array as $index => $chord) {
			foreach ($chord['inversions'] as $key => $inversion) {
				$this->array[$index]['inversions'][$key]['label'] = $this->read($inversion);
			}
		}

		return $this->array;
	}

	public function find($notes, $interval)
	{
		foreach ($notes['intervals'] as $note) {
			if ($note['interval'] == $interval)
				return $note;
		}
	}

	public function read($inversion)
	{
		$root = $this->root($inversion);
		$bass = $this->bass()['bass'] && $this->bass()['bass'][0] != $root['root'][0] ? '/' . $this->bass()['bass'] : null;
		$type = $this->core($inversion);
		$seventh = $this->seventh($inversion);
		$sus = $this->sus($inversion);
		$ext = $this->extensions($inversion);

		$full = [
			'full_shorthand' => $root['root'] . $type['type_shorthand'] . $seventh['seventh_shorthand'] . $sus['sus_shorthand'] . $ext['ext_shorthand'] . $bass
		];

		if (strhas($full['full_shorthand'], '9'))
			$full['full_shorthand'] = str_replace('7', '', $full['full_shorthand']);

		if (strhas($full['full_shorthand'], '11')) {
			$full['full_shorthand'] = str_replace('7', '', $full['full_shorthand']);

			if (! strhas($full['full_shorthand'], 'm9'))
				$full['full_shorthand'] = str_replace('9', '', $full['full_shorthand']);
		}

		if (strhas($full['full_shorthand'], '13')) {
			$full['full_shorthand'] = str_replace('7', '', $full['full_shorthand']);

			if (! strhas($full['full_shorthand'], 'm9'))
				$full['full_shorthand'] = str_replace('9', '', $full['full_shorthand']);

			if (! strhas($full['full_shorthand'], '-11') && ! strhas($full['full_shorthand'], '+11'))
				$full['full_shorthand'] = str_replace('11', '', $full['full_shorthand']);
		}

		return array_merge($root, $this->bass(), $type, $seventh, $sus, $ext, $full);
	}
}
