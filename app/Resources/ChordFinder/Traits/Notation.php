<?php

namespace App\Resources\ChordFinder\Traits;

trait Notation
{
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
		$label = [];
		$third = $this->find($notes, 3);
		$fifth = $this->find($notes, 5);
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

		if ($third['type'] == 'major' && $fifth['type'] == 'augmented') {
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
				$label['seventh_shorthand'] = '+7';
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
			$label['seventh'] = $seventh['name'];
			$label['seventh_shorthand'] = $seventh['shorthand'] . '7';
		}

		return $label;
	}

	public function sus($notes)
	{
		$label = [];
		$second = $this->find($notes, 2);
		$third = $this->find($notes, 3);
		$fourth = $this->find($notes, 4);

		if (is_null($second) && is_null($fourth))
			return ['sus' => '', 'sus_shorthand' => ''];

		if (is_null($third)) {
			if ($second && $fourth) {
				$label['sus'] = 'suspended 2 and 4';
				$label['sus_shorthand'] = sup('sus24');
			} else if ($second && is_null($fourth)) {
				$label['sus'] = 'suspended 2';
				$label['sus_shorthand'] = sup('sus2');				
			} else if (is_null($second) && $fourth) {
				$label['sus'] = 'suspended 4';
				$label['sus_shorthand'] = sup('sus4');				
			}
		} else {
			if ($second && $fourth) {
				$label['sus'] = 'added 2 and 4';
				$label['sus_shorthand'] = sup('add24');
			} else if ($second && is_null($fourth)) {
				$label['sus'] = 'added 2';
				$label['sus_shorthand'] = sup('add2');				
			} else if (is_null($second) && $fourth) {
				$label['sus'] = 'added 4';
				$label['sus_shorthand'] = sup('add4');				
			}
		}

		return $label;
	}

	public function extensions($notes)
	{
		$label = ['ext' => '', 'ext_shorthand' => ''];
		$extensions = [6,9,11,13];

		foreach ($extensions as $extension) {
			$note = $this->find($notes, $extension);

			if ($note) {
				if ($note['type'] == 'major') {
					$label['ext'] .= $note['interval'];
					$label['ext_shorthand'] .= sup($note['interval']);
				} else if ($note['type'] == 'minor') {
					$label['ext'] .= 'm' . $note['interval'];
					$label['ext_shorthand'] .= sup('m' . $note['interval']);
				}
			}
		}

		return $label;
	}
}
