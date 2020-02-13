<?php

namespace App\Mail\Traits;

class Tutorials
{
	public function get()
	{
		return [
			[
				'piece' => 'III. Presto from Sonata Op.27 No.2 "Moonlight" by L.V. Beethoven',
				'title' => 'How to play fast repeated notes without accumulating tension on your wrists',
				'description' => 'The stormy final movement is in unexpected and powerful contrast to the sonata\'s intimate beginning. An effective performance of this movement demands lively and skillful playing, great stamina, and is significantly more demanding technically than the 1st and 2nd movements. In this tutorial, we explore the last theme of the third movement, where both hands play fast repeated chords. This is something that every pianist struggles with as it easily accumulates tension making your arm and wrists tired. We talk in detail how to practice this part to develop accuracy and avoid stiff movements.',
				'level' => 'advanced'
			],
			[
				'piece' => 'Autumn sketch from 24 Lyric Preludes in Romantic Style by W. Gillock',
				'title' => 'How to balance the sound between your hands',
				'description' => 'Using this romantic piece by Gillock, we analyze the melodic lines and how to improve on them by balancing the sound between hands, carefully discerning the sound produced by your right hand (bearer of the melody) from the one produced by your left hand (accompaniment).',
				'level' => 'intermediate'
			],
			[
				'piece' => 'The bear from Op.69 No.3 by D. Shostakovich',
				'title' => 'Polishing repeated notes and improve the accuracy of your jumps with the "stop and prepare" technique',
				'description' => 'In this tutorial, we review a couple of fundamental tips: polishing repeated notes and jumps accuracy. Let\'s learn how to improve your musical phrase when playing repeated notes by avoiding an overly percussive sound. Review the "stop and prepare" technique and understand how it can help us perform this piece fluently.',
				'level' => 'beginner'
			],
			[
				'piece' => 'Short Story by H. Lichner',
				'title' => 'Learn how to play full scales with even and clean sound',
				'description' => 'We use this beginner piece to talk about a common topic in piano playing: scales. And specifically one of the fundamentals about scales, which is keeping an even and clean sound during the ascending and descending motion. We review the basics regarding the passage of the thumb and a good exercise for thumb mobility.',
				'level' => 'beginner'
			]
		];
	}
}
