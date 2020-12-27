<?php

namespace App\Resources\FindYourMatch\Traits;

trait Questions
{
	// TOP TAGS: DREAMY, PLAYFUL, TENDER, HAPPY, MELANCHOLIC, RELAXING, CALM, ELEGANT, SAD, MYSTERIOUS
	protected $questions = [
		'What would you listen to during a relaxing Sunday afternoon?' => [
			['label' => 'Goldberg Variations', 'subtitle' => 'by J.S.Bach', 'keywords' => ['type' => 'period', 'term' => 'baroque']],
			['label' => 'Romeo and Juliet', 'subtitle' => 'by P.Tchaikovsky', 'keywords' => ['type' => 'mood', 'term' => 'melancholic']],
			['label' => 'Moonlight Sonata', 'subtitle' => 'by L.V.Beethoven', 'keywords' => ['type' => 'mood', 'term' => 'sad']],
			['label' => 'Requiem in D minor', 'subtitle' => 'by W.A.Mozart', 'keywords' => ['type' => 'mood', 'term' => 'sad']],
			['label' => 'Firebird Suite', 'subtitle' => 'by I.Stravinsky', 'keywords' => ['type' => 'mood', 'term' => 'crazy']],
			['label' => 'Nocturne in C# minor', 'subtitle' => 'by F.Chopin', 'keywords' => ['type' => 'mood', 'term' => 'dreamy']]
		],

		'Which heart-melting theme do you like the most?' => [
			['label' => '2nd Piano Concerto', 'subtitle' => 'by Rachmaninoff', 'audio' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3', 'keywords' => ['type' => 'mood', 'term' => 'melancholic']],
			['label' => 'Ballade in G minor', 'subtitle' => 'by Chopin', 'keywords' => ['type' => 'mood', 'term' => 'dreamy']],
			['label' => 'Intermezzo Op.18 No.2', 'subtitle' => 'by Brahms', 'keywords' => ['type' => 'mood', 'term' => 'tender']],
			['label' => 'Le Nozze di Figaro', 'subtitle' => 'by Mozart', 'keywords' => ['type' => 'mood', 'term' => 'happy']],
			['label' => 'Widmung', 'subtitle' => 'by Schumann/Liszt', 'keywords' => ['type' => 'mood', 'term' => 'tender']],
			['label' => 'Pur Ti Miro', 'subtitle' => 'by Monteverdi', 'keywords' => ['type' => 'mood', 'term' => 'relaxing']]
		],

		'Pick the music genre you connect with the most' => [
			['label' => 'Baroque', 'subtitle' => '1600 to 1750', 'keywords' => ['type' => 'period', 'term' => 'baroque']],
			['label' => 'Classical', 'subtitle' => '1750 to 1810', 'keywords' => ['type' => 'period', 'term' => 'classical']],
			['label' => 'Romantic', 'subtitle' => '1810 to 1900', 'keywords' => ['type' => 'period', 'term' => 'romantic']],
			['label' => 'Impressionist', 'subtitle' => '1890 to 1930', 'keywords' => ['type' => 'period', 'term' => 'impressionist']],
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

		'How would you describe your reading skills now?' => [
			['label' => 'I just started learning', 'subtitle' => 'I haven\'t been playing for long', 'keywords' => ['type' => 'level', 'term' => 'elementary']],
			['label' => 'Not too good', 'subtitle' => 'It takes me a while to read my pieces', 'keywords' => ['type' => 'level', 'term' => 'late beginner']],
			['label' => 'I\'m not good with the Bass clef', 'subtitle' => 'I can read treble clef well, just not the bass', 'keywords' => ['type' => 'level', 'term' => 'early beginner']],
			['label' => 'I think can read farily well', 'subtitle' => 'It\'s usually not an issue', 'keywords' => ['type' => 'level', 'term' => 'early intermediate']],
			['label' => 'Sight-reading makes me nervous', 'subtitle' => 'I can read well, but my sight-reading is slow', 'keywords' => ['type' => 'level', 'term' => 'early intermediate']],
			['label' => 'My reading skills are great', 'subtitle' => 'I\'ve no problem with this', 'keywords' => ['type' => 'level', 'term' => 'advanced']]
		],

		'If you had to hang out with one famous composer, who would that be?' => [
			['label' => 'F.Liszt', 'keywords' => ['type' => 'mood', 'term' => 'passionate']],
			['label' => 'Prokofiev', 'keywords' => ['type' => 'period', 'term' => 'modern']],
			['label' => 'J.S.Bach', 'keywords' => ['type' => 'period', 'term' => 'baroque']],
			['label' => 'L.V.Beethoven', 'keywords' => ['type' => 'mood', 'term' => 'mysterious']],
			['label' => 'F.Chopin', 'keywords' => ['type' => 'mood', 'term' => 'melancholic']],
			['label' => 'W.A.Mozart', 'keywords' => ['type' => 'mood', 'term' => 'playful']]
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
			['label' => 'I\'ve been playing for a while but lost motivation', 'subtitle' => 'Trying to get back on track', 'keywords' => ['type' => 'level', 'term' => 'late intermediate']],
			['label' => 'I don\'t like playing concerts, I play just for myself', 'subtitle' => 'I like playing, but not practicing', 'keywords' => ['type' => 'level', 'term' => 'late intermediate']],
			['label' => 'I am a professional pianist', 'subtitle' => 'I\'m excited to start!', 'keywords' => ['type' => 'level', 'term' => 'advanced']]
		],
	];
}
