<?php

namespace App\Games\TrueOrFalse;

trait Statements
{
	protected $easy = [
		// THEORY
		'2/4 means <u>4 quarter notes</u> per measure' => false,
		'<strong>G B D</strong> are the notes in a <u>G major chord</u>' => true,
		'<strong>B to F</strong> is a <u>perfect 5th</u>' => false,
		'<strong>F to A</strong> is a <u>major 3rd</u>' => true,
		'A basic chord is made up of <strong>3 notes</strong>' => true,
		'<strong>P</strong> means soft' => true,
		// PIANO
		'The piano has <u>88 keys</u>' => true,
		'The <strong>Damper Pedal</strong> makes the piano sound <u>softer</u>' => false,
		// COMPOSERS
		'J.S.Bach was born in <u>Germany</u>' => true,
		'L.V.Beethoven was the last romantic composer' => false,
		'Clara Schumann was a very famous pianist in the 1800s' => true,
		'Bach was a prominant composer in the <u>classical period</u>' => false,
		// HISTORY
		'The <strong>Baroque period</strong> ended in <u>1850</u>' => false
	];

	protected $difficult = [
		// THEORY
		'<strong>F to Db</strong> is a <u>minor 6th</u>' => true, 
		'<strong>G B D F</strong> are the notes in a <u>dominant 7th chord</u>' => true, 
		'<strong>Gb to Cb</strong> is an <u>augmented 4th</u>' => false,
		'The key of <strong>Gb major</strong> has <u>6 flats</u>' => true,
		// PIANO
		'The piano was invented by Heinrich Steinway in 1836' => false,
		'A grand piano\'s strings exert a combined force of <u>20 tonnes</u> on the cast iron frame' => true,
		// COMPOSERS
		'B.Bartók was born in <u>Hungary</u>' => true, 
		'Rachmaninoff is buried in <u>Moscow</u>' => false, 
		'C.Debussy composed <u>9 Symphonies</u>' => false, 
		'W.A.Mozart died when he was <u>52 years old</u>' => false, 
		'L.V.Beethoven was a life long admirer of Haydn\'s music' => false, 
		'L.V.Beethoven met with W.A.Mozart in 1789' => false, 
		'F.Chopin dedicated his Études Opus 10 to F.Liszt' => true, 
		'Debussy <u>did not</u> consider his music to be <i>Impressionistic</i>, but rather <i>Symbolistic</i>' => true, 
		'P.Tchaikovsky admired the music of J.Brahms' => false,
		'Rossini wrote the aria "Di tanti palpiti" while waiting for some risotto in a Venice restaurant' => true,
	];
}
