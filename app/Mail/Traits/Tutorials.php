<?php

namespace App\Mail\Traits;

class Tutorials
{
	public function get()
	{
		return [
			[
				'piece' => 'Clair de Lune by C. Debussy',
				'title' => 'Debussy\'s use of syncopations in the main theme',
				'description' => 'In this video, we explore how Debussy uses syncopations to shape the main theme of this iconic piece. The expressive qualities of this music are largely defined by how the 8th notes are anticipated, creating harmonic and rhythmic suspension. The end result is a gentle theme that has captivated audiences ever since it was composed.',
				'level' => 'advanced'
			],
			[
				'piece' => 'Les Barricades Mistérieuses by F. Couperin',
				'title' => 'Musical form and contrasts between sections',
				'description' => 'We fell in love with this piece and certainly you will too! Learn about the Rondo, one of the most famous musical forms in baroque French music that made its way into the Classical period and beyond. We\'ll also explore how Couperin created contrasts between the sections through harmonic and thematic development.',
				'level' => 'intermediate'
			],
			[
				'piece' => 'Little waltz Op.205 No.10 by C. Gurlitt',
				'title' => 'A complete harmonic analysis',
				'description' => 'Let\'s dive into the harmony and understand the name and functions of each chord. Measure by measure, we\'ll investigate each chord as we go along and this will help you understand some of the expressive content you\'ll find in this music, which is intrinsically related to its harmonic progressions.',
				'level' => 'beginner'
			],
			[
				'piece' => 'Snowflakes gently falling by D.G. Rahbee',
				'title' => 'Better accuracy with the "stop and prepare" technique',
				'description' => 'Rahbee is an incredible contemporary composer with a vast amount of compositions that range from elementary to advanced levels. "Snowflakes gently falling" is a wonderful piece for beginners to explore fine nuances of sound and move along a large section of the keyboard. We\'ll learn the "stop and prepare" technique that will help you move your arms and change positions with good balance and accuracy.',
				'level' => 'elementary'
			]
		];
	}
}
