<?php

namespace App\Resources;

class CircleOfFifths
{
	protected $key, $mode, $index;
	protected $modes = [
		'full' => [' major', ' minor', ' minor', ' major', ' major', ' minor', ' dim'],
		'short' => ['M', 'm', 'm', 'M', 'M', 'm', '&#176;'],
	];
	protected $model = [
		['c', 'd', 'e', 'f', 'g', 'a', 'b'],
		['db', 'eb', 'f', 'gb', 'ab', 'bb', 'c'],
		['d', 'e', 'f#', 'g' ,'a', 'b', 'c#'],
		['eb', 'f', 'g', 'ab', 'bb', 'c', 'd'],
		['e', 'f#', 'g#', 'a', 'b', 'c#', 'd#'],
		['f', 'g', 'a', 'bb', 'c', 'd', 'e'],
		['f#', 'g#', 'a#', 'b', 'c#', 'd#', 'e'],
		['g', 'a', 'b', 'c', 'd', 'e', 'f#'],
		['ab', 'bb', 'c', 'dd', 'eb', 'f', 'g'],
		['a', 'b', 'c#', 'd', 'e', 'f#', 'g#'],
		['bb', 'c', 'd', 'eb', 'f', 'g', 'a'],
		['b', 'c#', 'd#', 'e', 'f#', 'g#', 'a#']
	];

	protected $enharmonicModel = [
		['b#', 'c##', 'd##', 'e#', 'f##', 'g##', 'a##'],
		['c#', 'd#', 'e#', 'f#', 'g#', 'a#', 'b#'],
		['ebb', 'fb', 'gb', 'abb' ,'bbb', 'cb', 'db'],
		['d#', 'e#', 'f##', 'g#', 'a#', 'b#', 'c##'],
		['fb', 'gb', 'ab', 'bbb', 'cb', 'db', 'eb'],
		['gbb', 'abb', 'bbb', 'cbb', 'dbb', 'ebb', 'fb'],
		['gb', 'ab', 'bb', 'cb', 'db', 'eb', 'f'],
		['abb', 'bbb', 'cb', 'dbb', 'ebb', 'fb', 'gb'],
		['g#', 'a#', 'b#', 'c#', 'd#', 'e#', 'f##'],
		['bbb', 'cb', 'db', 'eb', 'fb', 'gb', 'ab'],
		['a#', 'b#', 'c##', 'd#', 'e#', 'f##', 'g##'],
		['cb', 'db', 'eb', 'fb', 'gb', 'ab', 'bb']
	];

	public function find($key)
	{
		foreach ($this->model as $index => $array) {
			if ($array[0] == $key) {
				$this->key = $array;
				$this->index = $index;
				break;
			}
		}

		if (! $this->key)
			abort(404, 'The key of ' . $key . ' could not be found.');

		$this->setMajor();

		return $this;
	}

	public function setMajor()
	{
		$this->mode = 'major';
		$this->key = $this->model[$this->index];

		return $this;
	}

	public function setMinor()
	{
		$this->mode = 'minor';
		$this->key = $this->model[$this->index];

		return $this;
	}

	public function setEnharmonic()
	{
		$this->mode = 'enharmonic';
		$this->key = $this->enharmonicModel[$this->index];

		return $this;		
	}

	public function getMajorKey()
	{
		return ucfirst($this->key[0]) . ' major';
	}

	public function getMinorKey()
	{
		return ucfirst($this->key[5]) . ' minor';
	}

	public function getNeighbors()
	{
		return json_encode([
			$this->keyToString(2),
			$this->keyToString(3),
			$this->keyToString(4),
			$this->keyToString(5)
		]);
	}

	public function getRomanNumerals()
	{
		if ($this->mode == 'minor') {
			$numerals = [
				'i' => $this->keyToString(6, 'short'),
				'ii' => $this->keyToString(7, 'short'),
				'III' => $this->keyToString(1, 'short'),
				'iv' => $this->keyToString(2, 'short'),
				'v' => $this->keyToString(3, 'short'),
				'VI' => $this->keyToString(4, 'short'),
				'VII' => $this->keyToString(5, 'short')
			];
		} else {
			$numerals = [
				'I' => $this->keyToString(1, 'short'),
				'ii' => $this->keyToString(2, 'short'),
				'iii' => $this->keyToString(3, 'short'),
				'IV' => $this->keyToString(4, 'short'),
				'V' => $this->keyToString(5, 'short'),
				'VI' => $this->keyToString(6, 'short'),
				'vii' => $this->keyToString(7, 'short')
			];
		}

		return json_encode($numerals);
	}

	public function getTonic()
	{
		$group = [];
		$modes = [
			'major' => [1,3,6],
			'minor' => [6,1,4],
			'enharmonic' => [1,3,6]
		];

		for ($i=0; $i<count($modes[$this->mode]); $i++) {
			array_push($group, $this->keyToString($modes[$this->mode][$i]));
		}

		return json_encode($group);
	}

	public function getSubdominant()
	{
		$group = [];
		$modes = [
			'major' => [4,2,6],
			'minor' => [2,6,4],
			'enharmonic' => [4,2,6]
		];

		for ($i=0; $i<count($modes[$this->mode]); $i++) {
			array_push($group, $this->keyToString($modes[$this->mode][$i]));
		}

		return json_encode($group);
	}

	public function getDominant()
	{ 
		$group = [];
		$modes = [
			'major' => [5,7,3],
			'minor' => [],
			'enharmonic' => [5,7,3]
		];

		for ($i=0; $i<count($modes[$this->mode]); $i++) {
			array_push($group, $this->keyToString($modes[$this->mode][$i]));
		}

		if ($this->mode == 'minor') {
			$third = $this->key[0];
			$fifth = $this->key[2];
			$seventh = $this->key[4];
			$seventh = (strpos($this->key[4], 'b') !== false) ? $this->key[4][0] : $this->key[4] . 'b';

			array_push($group, ucfirst($fifth) . ' major*');
			array_push($group, ucfirst($seventh) . ' dim*');
			array_push($group, ucfirst($third) . ' aug*');
		}

		return json_encode($group);
	}

	public function keyToString($index, $format = 'full')
	{
		$index--;
		return ucfirst($this->key[$index]) . $this->modes[$format][$index];
	}

	public function next()
	{
		$index = array_search($this->key, $this->model);

		$index += 7;

		if ($index > 11)
			$index -= 12;
		
		return str_replace('#', 's', $this->model[$index][0]);
	}

	public function prev()
	{
		$index = array_search($this->key, $this->model);

		$index += 5;

		if ($index > 11)
			$index -= 12;
		
		return str_replace('#', 's', $this->model[$index][0]);
	}
}
