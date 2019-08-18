<?php

namespace App\Resources\ChordFinder;

class Error
{
	private $message;

	public function __construct($message = null)
	{
		$this->message = $message ?? 'We couldn\'t figure out what this chord is. Please check the notes and try again!';
	}

	public function report()
	{
		return $this->message;
	}
}
