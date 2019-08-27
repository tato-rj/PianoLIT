<?php

namespace App\Resources\ChordFinder\Traits;

trait Notation
{
	public function bass()
	{
		$bass = str_replace('-', 'b', $this->bass);
		$bass = str_replace('+', '#', $bass);
		$bass = str_replace('2', '', $bass);

		return ['bass' => $bass];	
	}

	public function root($notes)
	{
		$root = $notes['chord'][0];
		$root = str_replace('-', 'b', $root);
		$root = str_replace('+', '#', $root);
		$root = str_replace('2', '', $root);
		return ['root' => ucfirst($root)];		
	}

	public function core($notes)
	{
		$label = ['type' => null, 'type_shorthand' => null];
		$third = $this->find($notes, 3) ?? $this->find($notes, 10);
		$fifth = $this->find($notes, 5) ?? $this->find($notes, 12);
		$seventh = $this->find($notes, 7);

		if ($fifth['type'] == 'perfect' || is_null($fifth)) {
			$label['type'] = $third['type'];
			$label['type_shorthand'] = $third['type'] == 'minor' ? 'm' : '';
		}

		if ((is_null($third) || $third['type'] == 'minor') && $fifth['type'] == 'diminished') {
			if ($seventh['type'] == 'diminished') {
				$label['type'] = 'fully diminished';
				$label['type_shorthand'] = sup('o');
			} else if ($seventh['type'] == 'minor') {
				$label['type'] = 'half diminished';
				$label['type_shorthand'] = sup('ø');
			} else {
				$label['type'] = 'diminished';
				$label['type_shorthand'] = sup('o');
			}
		}

		if ((is_null($third) || $third['type'] == 'major') && $fifth['type'] == 'augmented') {
			$label['type'] = 'augmented';
			$label['type_shorthand'] = 'aug';
		}
		
		return $label;
	}

	public function seventh($notes)
	{
		$label = ['seventh' => '', 'seventh_shorthand' => ''];
		$third = $this->find($notes, 3);
		$fifth = $this->find($notes, 5);
		$seventh = $this->find($notes, 7);
		
		if (is_null($seventh))
			return $label;

		if (is_null($fifth) || $fifth['type'] == 'perfect') {
			if ($seventh['type'] == 'major') {
				$label['seventh'] = $third == 'major' ? 'dominant 7' : 'minor 7';
				$label['seventh_shorthand'] = ' maj7';
			} else if ($seventh['type'] == 'minor') {
				$label['seventh'] = 'minor 7';
				$label['seventh_shorthand'] = '7';
			}
		} else if ($seventh['type'] == 'diminished') {
			$label['seventh'] = $seventh['name'];
			$label['seventh_shorthand'] = '7';
		} else if ($fifth['type'] == 'diminished' && $seventh['type'] == 'minor') {
			$label['seventh'] = $seventh['name'];
			$label['seventh_shorthand'] = '7';
		} else {
			if ($seventh['type'] == 'major') {
				$label['seventh'] = 'major 7';
				$label['seventh_shorthand'] = ' maj7';
			} else if ($seventh['type'] == 'minor') {
				$label['seventh'] = 'minor 7';
				$label['seventh_shorthand'] = '7';
			}
		}

		return $label;
	}

	public function sus($notes)
	{
		$label = [];
		$second = $this->find($notes, 2);
		$third = $this->find($notes, 3);
		$fourth = $this->find($notes, 4);
		$secondType = $fourthType = null;

		if ($second) {
			if ($second['type'] == 'minor') {
				$secondType = 'b2';
			} else if ($second['type'] == 'augmented') {
				$secondType = '#2';
			}  else {
				$secondType = '2';
			}
		}

		if ($fourth)
			$fourthType = $fourth['type'] == 'augmented' ? '#4' : '4';

		if (is_null($second) && is_null($fourth))
			return ['sus' => '', 'sus_shorthand' => ''];

		if (is_null($third)) {
			if ($second && $fourth) {
				$label['sus'] = 'suspended '.$secondType.' and '.$fourthType;
				$label['sus_shorthand'] = sup('sus'.$secondType.$fourthType);
			} else if ($second && is_null($fourth)) {
				$label['sus'] = 'suspended '.$secondType;
				$label['sus_shorthand'] = sup('sus'.$secondType);				
			} else if (is_null($second) && $fourth) {
				$label['sus'] = 'suspended '.$fourthType;
				$label['sus_shorthand'] = sup('sus'.$fourthType);				
			}
		} else {
			if ($second && $fourth) {
				$label['sus'] = 'added '.$secondType.' and '.$fourthType;
				$label['sus_shorthand'] = sup('add'.$secondType.$fourthType);
			} else if ($second && is_null($fourth)) {
				$label['sus'] = 'added '.$secondType;
				$label['sus_shorthand'] = sup('add'.$secondType);				
			} else if (is_null($second) && $fourth) {
				$label['sus'] = 'added '.$fourthType;
				$label['sus_shorthand'] = sup('add'.$fourthType);				
			}
		}

		return $label;
	}

	public function extensions($notes)
	{
		$label = ['ext' => '', 'ext_shorthand' => ''];
		$extensions = [6,9,11,13];

		$third = $this->find($notes, 3);
		$fifth = $this->find($notes, 5);
		$prefix = ($third['type'] == 'major' && $fifth['type'] == 'diminished') ? 'b5' : null;

		foreach ($extensions as $extension) {
			$note = $this->find($notes, $extension);

			if ($note) {
				if ($note['type'] == 'major') {
					$label['ext'] .= $note['interval'];
					$label['ext_shorthand'] .= sup($prefix . $note['interval']);
				} else if ($note['type'] == 'minor') {
					$label['ext'] .= 'b' . $note['interval'];
					$label['ext_shorthand'] .= sup($prefix . 'b' . $note['interval']);
				} else if ($note['type'] == 'perfect') {
					$label['ext'] .= $note['interval'];
					$label['ext_shorthand'] .= sup($prefix . $note['interval']);
				} else if ($note['type'] == 'diminished') {
					$label['ext'] .= 'b' . $note['interval'];
					$label['ext_shorthand'] .= sup($prefix . 'b' . $note['interval']);
				} else if ($note['type'] == 'augmented') {
					$label['ext'] .= '#' . $note['interval'];
					$label['ext_shorthand'] .= sup($prefix . '#' . $note['interval']);
				}
			}
		}

		if (! $label['ext']) {
			$label['ext'] = $prefix;
			$label['ext_shorthand'] = sup($prefix);
		}

		return $label;
	}
}
