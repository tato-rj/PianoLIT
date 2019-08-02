<?php

namespace App\Resources\ChordFinder;

use App\Resources\ChordFinder\Traits\Factory;

class Interval
{
	use Factory;

	protected $first, $second, $absolute;

	public function __construct($first, $second = null, $absolute = false)
	{
		$this->absolute = $absolute;
		$this->first = $first;
		$this->second = $second;
	}

	public function read()
	{
		if (! $this->type())
			return abort(422, 'The interval between ' . $this->first . ' and ' . $this->second . ' is not valid.');

		$interval = $this->countLetters($octave = true);
		$steps = $this->countSteps($octave = true);
		$type = $this->type();

		return [
            'interval' => $interval,
            'steps' => $steps,
            'name' => $type . ' ' . $interval,
            'type' => $type,
            'shorthand' => $this->shorthand($type)
		];
	}

	public function type()
	{
		try {
			return $this->intervals($this->absolute)
					[$this->countLetters($octave = false)]
					[$this->countSteps($octave = false)];
		} catch (\Exception $e) {
			return null;
		}
	}

	public function shorthand($type)
	{
		if (in_array($type, ['augmented', 'diminished'])) {
			return substr($type, 0, 3);
		} else {
			return $type == 'minor' ? 'm' : 'M';
		}
	}

	public function countLetters($considerOctave = true)
	{
		$firstIndex = array_search($this->first[0], $this->letters);
		$secondIndex = array_search($this->second[0], array_slice($this->letters, $firstIndex)) + $firstIndex;

		$octave = strhas($this->second, '2') && $considerOctave ? 7 : 0;

		$interval = $secondIndex - $firstIndex + 1;

		return $interval == 2 ? $interval + $octave : $interval;
	}

	public function countSteps($considerOctave = true)
	{
		$firstIndex = array_search($this->first[0], $this->steps);
		$secondIndex = array_search($this->second[0], array_slice($this->steps, $firstIndex)) + $firstIndex;

		$firstIndex += substr_count($this->first, '+');
		$firstIndex -= substr_count($this->first, '-');
		$secondIndex += substr_count($this->second, '+');
		$secondIndex -= substr_count($this->second, '-');

		$octave = strhas($this->second, '2') && $considerOctave ? 12 : 0;

		$steps = $secondIndex - $firstIndex;	

		return in_array($steps, [1,2]) ? $steps + $octave : $steps;	
	}

	public function isEnharmonic()
	{
		try {
			$double =  strhas($this->first, '+') && strhas($this->second, '-') && next_letter($this->first) == $this->second[0];
			$single = $this->first == 'b' && $this->second == 'c-' || $this->first == 'e' && $this->second == 'f-' ||
						$this->first == 'e+' && $this->second == 'f' || $this->first == 'b+' && $this->second == 'c';	

			return $double || $single;	
		} catch (\Exception $e) {
			return false;
		}
	}

	public function getWhiteEnharmonic()
	{
		return array_key_exists($this->first, $this->whiteEnharmonics) ? $this->whiteEnharmonics[$this->first] : $this->first;
	}
}
