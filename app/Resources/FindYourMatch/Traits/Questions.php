<?php

namespace App\Resources\FindYourMatch\Traits;

trait Questions
{
	protected $questions = [
		'What would you listen to during a relaxing Sunday afternoon?' => [
			['label' => 'Goldberg Variations', 'subtitle' => 'by J.S.Bach', 'keywords' => ['type' => 'period', 'term' => 'baroque']],
			['label' => 'Romeo and Juliet', 'subtitle' => 'by P.Tchaikovsky', 'keywords' => ['type' => 'nationality', 'term' => 'russian']],
			['label' => 'Moonlight Sonata', 'subtitle' => 'by L.V.Beethoven', 'keywords' => ['type' => 'mood', 'term' => 'melancholic']],
			['label' => 'Requiem in D minor', 'subtitle' => 'by W.A.Mozart', 'keywords' => ['type' => 'mood', 'term' => 'dramatic']],
			['label' => 'Firebird Suite', 'subtitle' => 'by I.Stravinsky', 'keywords' => ['type' => 'mood', 'term' => 'modern']],
			['label' => 'Nocturne in C# minor', 'subtitle' => 'by F.Chopin', 'keywords' => ['type' => 'mood', 'term' => 'dreamy']]
		],

		'Which heart-melting theme do you like the most?' => [
			['label' => '2nd Piano Concerto', 'subtitle' => 'by Rachmaninoff', 'audio' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3', 'keywords' => ['type' => 'mood', 'term' => 'dramatic']],
			['label' => 'Ballade in G minor', 'subtitle' => 'by Chopin', 'keywords' => ['type' => 'mood', 'term' => 'melancholic']],
			['label' => 'Intermezzo Op.18 No.2', 'subtitle' => 'by Brahms', 'keywords' => ['type' => 'mood', 'term' => 'dreamy']],
			['label' => 'Le Nozze di Figaro', 'subtitle' => 'by Mozart', 'keywords' => ['type' => 'mood', 'term' => 'light']],
			['label' => 'Widmung', 'subtitle' => 'by Schumann/Liszt', 'keywords' => ['type' => 'mood', 'term' => 'passionate']],
			['label' => 'Pur Ti Miro', 'subtitle' => 'by Monteverdi', 'keywords' => ['type' => 'mood', 'term' => 'tender']]
		],

		'Pick the music genre you connect with the most' => [
			['label' => 'Renaissance', 'subtitle' => '1400 to 1600', 'keywords' => ['type' => 'mood', 'term' => 'serious']],
			['label' => 'Baroque', 'subtitle' => '1600 to 1750', 'keywords' => ['type' => 'period', 'term' => 'baroque']],
			['label' => 'Classical', 'subtitle' => '1750 to 1810', 'keywords' => ['type' => 'period', 'term' => 'classical']],
			['label' => 'Romantic', 'subtitle' => '1810 to 1900', 'keywords' => ['type' => 'period', 'term' => 'romantic']],
			['label' => 'Modern', 'subtitle' => '1900 to 1945', 'keywords' => ['type' => 'period', 'term' => 'modern']],
			['label' => 'Contemporary', 'subtitle' => '1945 to now', 'keywords' => ['type' => 'mood', 'term' => 'crazy']]
		],

		'Which would you say is your biggest challenge right now?' => [
			['label' => 'Sight-reading', 'subtitle' => 'It takes me too long to read anything', 'keywords' => ['type' => 'level', 'term' => 'early beginner']],
			['label' => 'Theory', 'subtitle' => 'I\'d like to know more about chords and harmony', 'keywords' => ['type' => 'level', 'term' => 'early intermediate']],
			['label' => 'Speed', 'subtitle' => 'Playing fast pieces is the hardest thing for me', 'keywords' => ['type' => 'level', 'term' => 'late intermediate']],
			['label' => 'Memorization', 'subtitle' => 'It\'s hard for me to memorize', 'keywords' => ['type' => 'level', 'term' => 'early intermediate']],
			['label' => 'Motivation', 'subtitle' => 'I like playing, but not practicing', 'keywords' => ['type' => 'level', 'term' => 'early intermediate']],
			['label' => 'I\'m a newby, it\'s all a challenge', 'subtitle' => 'I\'m excited to start!', 'keywords' => ['type' => 'level', 'term' => 'elementary']]
		],

		'Which composer you wish you knew more about?' => [
			['label' => 'Florence Price', 'subtitle' => '1887 to 1953', 'keywords' => ['type' => 'ethnicity', 'term' => 'black']],
			['label' => 'Alberto Ginastera', 'subtitle' => '1916 to 1983', 'keywords' => ['type' => 'period', 'term' => 'modern']],
			['label' => 'Thomas Wiggins', 'subtitle' => '1849 to 1908', 'keywords' => ['type' => 'ethnicity', 'term' => 'black']],
			['label' => 'Ulysses Kay', 'subtitle' => '1917 to 1995', 'keywords' => ['type' => 'ethnicity', 'term' => 'black']],
			['label' => 'Amy Beach', 'subtitle' => '1867 to 1944', 'keywords' => ['type' => 'gender', 'term' => 'female']],
			['label' => 'Mel Bonis', 'subtitle' => '1923 to 2006', 'keywords' => ['type' => 'gender', 'term' => 'female']]
		],

		'If you had to hang out with one famous composer, who would that be?' => [
			['label' => 'F.Liszt', 'keywords' => ['type' => 'composer', 'term' => 'liszt']],
			['label' => 'Prokofiev', 'keywords' => ['type' => 'composer', 'term' => 'prokofiev']],
			['label' => 'J.S.Bach', 'keywords' => ['type' => 'composer', 'term' => 'bach']],
			['label' => 'L.V.Beethoven', 'keywords' => ['type' => 'composer', 'term' => 'beethoven']],
			['label' => 'F.Chopin', 'keywords' => ['type' => 'composer', 'term' => 'chopin']],
			['label' => 'W.A.Mozart', 'keywords' => ['type' => 'composer', 'term' => 'mozart']]
		],

		'Which of these hidden gems do you like the most?' => [
			['label' => 'Elegy', 'subtitle' => 'by Shostakovich', 'keywords' => ['type' => 'mood', 'term' => 'tender']],
			['label' => 'Souvenance', 'subtitle' => 'by Chaminade', 'keywords' => ['type' => 'gender', 'term' => 'female']],
			['label' => 'Dreaming', 'subtitle' => 'by Amy Beach', 'keywords' => ['type' => 'gender', 'term' => 'female']],
			['label' => 'Sewing Song', 'subtitle' => 'by Thomas Wiggins', 'keywords' => ['type' => 'ethnicity', 'term' => 'black']],
			['label' => 'Song Without Words', 'subtitle' => 'by Mel Bonis', 'keywords' => ['type' => 'gender', 'term' => 'female']],
			['label' => 'Little Melody', 'subtitle' => 'Florence Price', 'keywords' => ['type' => 'ethnicity', 'term' => 'black']]
		],

		'Which option best describes where you are right now in your piano journey?' => [
			['label' => 'I just got started', 'subtitle' => 'It\'s been less than a year', 'keywords' => ['type' => 'level', 'term' => 'elementary']],
			['label' => 'I\'ve been playing Bach minuets', 'subtitle' => 'My reading is ok, but it\'s getting better!', 'keywords' => ['type' => 'level', 'term' => 'early beginner']],
			['label' => 'I can play well but reading is still a challenge', 'subtitle' => 'Playing fast pieces is the hardest thing for me', 'keywords' => ['type' => 'level', 'term' => 'early intermediate']],
			['label' => 'I\'ve been playing for a while but lost motivation', 'subtitle' => 'It\'s hard for me to memorize', 'keywords' => ['type' => 'level', 'term' => 'late intermediate']],
			['label' => 'I don\'t like playing concerts, I play just for myself', 'subtitle' => 'I like playing, but not practicing', 'keywords' => ['type' => 'level', 'term' => 'late intermediate']],
			['label' => 'I am a professional pianist', 'subtitle' => 'I\'m excited to start!', 'keywords' => ['type' => 'level', 'term' => 'advanced']]
		],
	];
}
