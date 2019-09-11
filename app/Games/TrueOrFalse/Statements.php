<?php

namespace App\Games\TrueOrFalse;

trait Statements
{
	protected $easy = [
		'The piano has <u>88 keys</u>' => true,
		'J.S.Bach was born in <u>Germany</u>' => true,
		'The <strong>Damper Pedal</strong> makes the piano sound <u>softer</u>' => false,
		'<strong>G B D</strong> are the notes in a <u>G major chord</u>' => true,
		'<strong>P</strong> means soft' => false,
		'2/4 means <u>4 quarter notes</u> per measure' => false,
		'L.V.Beethoven was the last romantic composer' => false,
		'<strong>F to A</strong> is a <u>major 3rd</u>' => true,
		'<strong>B to F</strong> is a <u>Perfect 5th</u>' => false,
		'Clara Schumann was a very famous pianist in the 1800s' => true,
		'A basic chord is made up of <strong>3 notes</strong>' => true,
		'Bach was a prominant composer in the <u>classical period</u>' => false
	];

	protected $difficult = [
		'The piano was invented by Heinrich Steinway in 1836' => false, 
		'B.Bartók was born in <u>Hungary</u>' => true, 
		'Rachmaninoff is buried in <u>Moscow</u>' => false, 
		'<strong>G B D F</strong> are the notes in a <u>Dominant 7th chord</u>' => true, 
		'C.Debussy composed <u>9 Symphonies</u>' => false, 
		'W.A.Mozart died when he was <u>52 years old</u>' => false, 
		'L.V.Beethoven was a life long admirer of Haydn\'s music' => false, 
		'<strong>F to Db</strong> is a <u>Minor 6th</u>' => true, 
		'L.V.Beethoven met with W.A.Mozart in 1789' => false, 
		'F.Chopin dedicated his Études Opus 10 to F.Liszt' => true, 
		'Debussy <u>did not</u> consider his music to be <i>Impressionistic</i>, but rather <i>Symbolistic</i>' => true, 
		'P.Tchaikovsky admired the music of J.Brahms' => false
	];
}
