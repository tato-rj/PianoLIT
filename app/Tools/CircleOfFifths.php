<?php

namespace App\Tools;

class CircleOfFifths
{
	protected $key;
	protected $mode;
	protected $modes = [
		'full' => [' major', ' minor', ' minor', ' major', ' major', ' minor', ' diminished'],
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

	public function find($key)
	{
		$this->setMajor();

		foreach ($this->model as $array) {
			if ($array[0] == $key) {
				$this->key = $array;
				break;
			}
		}

		if (! $this->key)
			abort(404, 'The key of ' . $key . ' could not be found.');

		return $this;
	}

	public function setMajor()
	{
		$this->mode = 'major';

		return $this;
	}

	public function setMinor()
	{
		$this->mode = 'minor';

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

	public function getNegative()
	{
		$third = (strpos($this->key[2], '#') !== false) ? $this->key[2][0] : $this->key[2] . 'b';
		$sixth = (strpos($this->key[5], '#') !== false) ? $this->key[5][0] : $this->key[5] . 'b';
		$seventh = (strpos($this->key[6], '#') !== false) ? $this->key[6][0] : $this->key[6] . 'b';
		
		if ($this->mode == 'major') {
			$map = [
				'regular' => [
					$this->keyToString(1, 'short'),
					$this->keyToString(2, 'short'),
					$this->keyToString(3, 'short'),
					$this->keyToString(4, 'short'),
					$this->keyToString(5, 'short'),
					$this->keyToString(6, 'short'),
					$this->keyToString(7, 'short'),
				],
				'negative' => [
					ucfirst($this->key[0]) . 'm',
					ucfirst($seventh) . 'M',
					ucfirst($sixth) . 'M',
					ucfirst($this->key[4]) . 'm',
					ucfirst($this->key[3]) . 'm',
					ucfirst($third) . 'M',
					ucfirst($this->key[1]) . '&#176;'
				]
			];
		} else {
			$map = [
				'regular' => [
					$this->keyToString(6, 'short'),
					$this->keyToString(7, 'short'),
					$this->keyToString(1, 'short'),
					$this->keyToString(2, 'short'),
					$this->keyToString(3, 'short'),
					$this->keyToString(4, 'short'),
					$this->keyToString(5, 'short'),
				],
				'negative' => [
					ucfirst($third) . 'M',
					ucfirst($this->key[1]) . '&#176;',
					ucfirst($this->key[0]) . 'm',
					ucfirst($seventh) . 'M',
					ucfirst($sixth) . 'M',
					ucfirst($this->key[4]) . 'm',
					ucfirst($this->key[3]) . 'm',
				]
			];
		}

		return json_encode($map);
	}

	public function getRomanNumerals()
	{
		if ($this->mode == 'major') {
			$numerals = [
				'I' => $this->keyToString(1, 'short'),
				'ii' => $this->keyToString(2, 'short'),
				'iii' => $this->keyToString(3, 'short'),
				'IV' => $this->keyToString(4, 'short'),
				'V' => $this->keyToString(5, 'short'),
				'VI' => $this->keyToString(6, 'short'),
				'vii' => $this->keyToString(7, 'short')
			];
		} else {
			$numerals = [
				'i' => $this->keyToString(6, 'short'),
				'ii' => $this->keyToString(7, 'short'),
				'III' => $this->keyToString(1, 'short'),
				'iv' => $this->keyToString(2, 'short'),
				'v' => $this->keyToString(3, 'short'),
				'VI' => $this->keyToString(4, 'short'),
				'VII' => $this->keyToString(5, 'short')
			];
		}

		return json_encode($numerals);
	}

	public function getTonic()
	{
		$group = [];
		$modes = [
			'major' => [1,3,6],
			'minor' => [6,1,4]
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
			'minor' => [2,6,4]
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
			'minor' => []
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
			array_push($group, ucfirst($seventh) . ' diminished*');
			array_push($group, ucfirst($third) . ' augmented*');
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
