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
		'The <strong>Una Corda Pedal</strong> makes the piano sound <u>softer</u>' => true,
		'The <strong>Sustain Pedal</strong> makes the notes sound <u>shorter</u>' => false,
		'The piano has the <strong>widest range</strong> of tones of all instruments' => true,
		// COMPOSERS
		'J.S.Bach was born in <u>Germany</u>' => true,
		'L.V.Beethoven was the last romantic composer' => false,
		'Clara Schumann was a very famous pianist in the 1800s' => true,
		'J.S.Bach was a prominant composer in the <u>classical period</u>' => false,
		'W.A.Mozart composed his first piece at the age of 5' => true,
		// HISTORY
		'The <strong>Baroque period</strong> ended in <u>1850</u>' => false,
		'The <strong>Romandic period</strong> came right after the <u>Baroque period</u>' => false,
		'The <strong>Classical period</strong> is considered to have started in 1750' => true,
	];

	protected $difficult = [
		// THEORY
		'<strong>F to Db</strong> is a <u>minor 6th</u>' => true, 
		'<strong>G B D F</strong> are the notes in a <u>dominant 7th chord</u>' => true, 
		'<strong>Gb to Cb</strong> is an <u>augmented 4th</u>' => false,
		'The key of <strong>Gb major</strong> has <u>6 flats</u>' => true,
		// PIANO
		'The piano was invented by <u>Heinrich Steinway</u> in 1836' => false,
		'The piano has over <strong>12,000 parts</strong>, 10,000 of which are moving' => true,
		'A grand piano\'s strings exert a combined force of <u>20 tonnes</u> on the cast iron frame' => true,
		'The <u>action on a grand piano is faster</u> than the one on an upright, allowing you to play faster' => true,
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
		// HISTORY
		'The Paris Conservatoire was founded when Beethoven was in his 20s' => true,
		'The String Quartet was developed into its current form by W.A.Mozart' => false,
	];
}
