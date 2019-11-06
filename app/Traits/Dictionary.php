<?php

namespace App\Traits;

trait Dictionary
{
	protected $ignore = [
		'the',
		'that',
		'with',
		'those',
		'of',
		'for',
		'piece',
		'pieces',
		'repertoire',
		'good',
		'best'
	];

	protected $substitute = [
		'beginners' => 'beginner'
	];
}
