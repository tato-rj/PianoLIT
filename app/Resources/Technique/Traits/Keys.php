<?php

namespace App\Resources\Technique\Traits;

trait Keys
{
	protected $keys = [
		'A-' => [
			'mode' => 'major',
			'label' => 'Ab major',
			'notes' => ['a-', 'b-', 'c', 'd-', 'e-', 'f', 'g'],
			'scale' => [
				'diatonic' => [
					['rh' => [3,4,1,2,3,1,2,3], 'lh' => [3,2,1,4,3,2,1,3]]
				]
			],
			'arpeggio' => [
				'triad' => [
					['rh' => [2,1,2,4], 'lh' => [2,1,4,2]],
					['rh' => [1,2,4,5], 'lh' => [5,4,2,1]],
					['rh' => [2,4,1,2], 'lh' => [4,2,1,2]],
				]
			]
		],
		'A-m' => [
			'mode' => 'minor',
			'label' => 'Ab minor',
			'notes' => ['a-', 'b-', 'c-', 'd-', 'e-', 'f-', 'g-'],
			'scale' => [
				'diatonic' => [
					['rh' => [3,4,1,2,3,1,2,3], 'lh' => [3,2,1,3,2,1,4,3]]
				]
			],
			'arpeggio' => [
				'triad' => [
					['rh' => [2,1,2,4], 'lh' => [2,1,4,2]],
					['rh' => [1,2,4,5], 'lh' => [5,4,2,1]],
					['rh' => [2,4,1,2], 'lh' => [4,2,1,2]],
				]
			]
		],
		'A' => [
			'mode' => 'major',
			'label' => 'A major',
			'notes' => ['a', 'b', 'c+', 'd', 'e', 'f+', 'g+'],
			'scale' => [
				'diatonic' => [
					['rh' => [1,2,3,1,2,3,4,5], 'lh' => [5,4,3,2,1,3,2,1]]
				]
			],
			'arpeggio' => [
				'triad' => [
					['rh' => [1,2,3,5], 'lh' => [5,3,2,1]],
					['rh' => [2,1,2,4], 'lh' => [4,2,1,2]],
					['rh' => [1,2,4,5], 'lh' => [5,3,2,1]],
				]
			]
		],
		'Am' => [
			'mode' => 'minor',
			'label' => 'A minor',
			'notes' => ['a', 'b', 'c', 'd', 'e', 'f', 'g'],
			'scale' => [
				'diatonic' => [
					['rh' => [1,2,3,1,2,3,4,5], 'lh' => [5,4,3,2,1,3,2,1]]
				]
			],
			'arpeggio' => [
				'triad' => [
					['rh' => [1,2,3,5], 'lh' => [5,4,2,1]],
					['rh' => [1,2,4,5], 'lh' => [5,4,2,1]],
					['rh' => [1,2,4,5], 'lh' => [5,3,2,1]],
				]
			]
		],
		'A+' => [
			'mode' => 'major',
			'label' => 'A# major',
			'notes' => ['a+', 'b+', 'c++', 'd+', 'e+', 'f++', 'g++'],
			'scale' => 'B-',
			'arpeggio' => 'B-'
		],
		'A+m' => [
			'mode' => 'minor',
			'label' => 'A# minor',
			'notes' => ['a+', 'b+', 'c+', 'd+', 'e+', 'f+', 'g+'],
			'scale' => 'B-m',
			'arpeggio' => 'B-m'
		],
		'B-' => [
			'mode' => 'major',
			'label' => 'Bb major',
			'notes' => ['b-', 'c', 'd', 'e-', 'f', 'g', 'a'],
			'scale' => [
				'diatonic' => [
					['rh' => [2,1,2,3,1,2,3,4], 'lh' => [3,2,1,4,3,2,1,3]]
				]
			],
			'arpeggio' => [
				'triad' => [
					['rh' => [2,1,2,4], 'lh' => [3,2,1,3]],
					['rh' => [1,2,4,5], 'lh' => [5,4,2,1]],
					['rh' => [1,2,4,5], 'lh' => [5,3,2,1]],
				]
			]
		],
		'B-m' => [
			'mode' => 'minor',
			'label' => 'Bb minor',
			'notes' => ['b-', 'c', 'd-', 'e-', 'f', 'g-', 'a-'],
			'scale' => [
				'diatonic' => [
					['rh' => [2,1,2,3,1,2,3,4], 'lh' => [2,1,3,2,1,4,3,2]]
				]
			],
			'arpeggio' => [
				'triad' => [
					['rh' => [2,3,1,2], 'lh' => [3,2,1,2]],
					['rh' => [2,1,2,3], 'lh' => [2,1,3,2]],
					['rh' => [1,2,3,5], 'lh' => [5,3,2,1]]
				]
			]
		],
		'B' => [
			'mode' => 'major',
			'label' => 'B major',
			'notes' => ['b', 'c+', 'd+', 'e', 'f+', 'g+', 'a+'],
			'scale' => [
				'diatonic' => [
					['rh' => [1,2,3,1,2,3,4,5], 'lh' => [4,3,2,1,4,3,2,1]]
				]
			],
			'arpeggio' => [
				'triad' => [
					['rh' => [1,2,3,5], 'lh' => [5,3,2,1]],
					['rh' => [2,3,1,2], 'lh' => [2,3,1,2]],
					['rh' => [2,1,2,3], 'lh' => [2,1,3,2]],
				]
			]
		],
		'Bm' => [
			'mode' => 'minor',
			'label' => 'B minor',
			'notes' => ['b', 'c+', 'd', 'e', 'f+', 'g', 'a'],
			'scale' => [
				'diatonic' => [
					['rh' => [1,2,3,1,2,3,4,5], 'lh' => [4,3,2,1,4,3,2,1]]
				]
			],
			'arpeggio' => [
				'triad' => [
					['rh' => [1,2,3,5], 'lh' => [5,4,2,1]],
					['rh' => [1,2,4,5], 'lh' => [5,4,2,1]],
					['rh' => [2,1,2,3], 'lh' => [4,2,1,2]],
				]
			]
		],
		'B+' => [
			'mode' => 'major',
			'label' => 'B# major',
			'notes' => ['b+', 'c++', 'd++', 'e+', 'f++', 'g++', 'a++'],
			'scale' => 'C',
			'arpeggio' => 'C'
		],
		'B+m' => [
			'mode' => 'minor',
			'label' => 'B# minor',
			'notes' => ['b+', 'c++', 'd+', 'e+', 'f++', 'g+', 'a+'],
			'scale' => 'Cm',
			'arpeggio' => 'Cm'
		],
		'C-' => [
			'mode' => 'major',
			'label' => 'Cb major',
			'notes' => ['c-', 'd-', 'e-', 'f-', 'g-', 'a-', 'b-'],
			'scale' => 'B',
			'arpeggio' => 'B'
		],
		'C-m' => [
			'mode' => 'minor',
			'label' => 'Cb minor',
			'notes' => ['c-', 'd-', 'e--', 'f-', 'g-', 'a--', 'b--'],
			'scale' => 'Bm',
			'arpeggio' => 'Bm'
		],
		'C' => [
			'mode' => 'major',
			'label' => 'C major',
			'notes' => ['c', 'd', 'e', 'f', 'g', 'a', 'b'],
			'scale' => [
				'diatonic' => [
					['rh' => [1,2,3,1,2,3,4,5], 'lh' => [5,4,3,2,1,3,2,1]]
				]
			],
			'arpeggio' => [
				'triad' => [
					['rh' => [1,2,3,5], 'lh' => [5,4,2,1]],
					['rh' => [1,2,4,5], 'lh' => [5,4,2,1]],
					['rh' => [1,2,4,5], 'lh' => [5,3,2,1]]
				]
			]
		],
		'Cm' => [
			'mode' => 'minor',
			'label' => 'C minor',
			'notes' => ['c', 'd', 'e-', 'f', 'g', 'a-', 'b-'],
			'scale' => [
				'diatonic' => [
					['rh' => [1,2,3,1,2,3,4,5], 'lh' => [5,4,3,2,1,3,2,1]]
				]
			],
			'arpeggio' => [
				'triad' => [
					['rh' => [1,2,3,5], 'lh' => [5,4,2,1]],
					['rh' => [2,1,2,3], 'lh' => [4,2,1,2]],
					['rh' => [1,2,3,5], 'lh' => [5,3,2,1]],
				]
			]
		],
		'C+' => [
			'mode' => 'major',
			'label' => 'C# major',
			'notes' => ['c+', 'd+', 'e+', 'f+', 'g+', 'a+', 'b+'],
			'scale' => [
				'diatonic' => [
					['rh' => [2,3,1,2,3,4,1,2], 'lh' => [3,2,1,4,3,2,1,2]]
				]
			],
			'arpeggio' => [
				'triad' => [
					['rh' => [2,1,2,4], 'lh' => [2,1,4,2]],
					['rh' => [1,2,4,5], 'lh' => [5,4,2,1]],
					['rh' => [2,4,1,2], 'lh' => [4,2,1,2]]
				]
			]
		],
		'C+m' => [
			'mode' => 'minor',
			'label' => 'C# minor',
			'notes' => ['c+', 'd+', 'e', 'f+', 'g+', 'a', 'b'],
			'scale' => [
				'diatonic' => [
					['rh' => [3,4,1,2,3,1,2,3], 'lh' => [3,2,1,4,3,2,1,2]]
				]
			],
			'arpeggio' => [
				'triad' => [
					['rh' => [2,1,2,4], 'lh' => [2,1,4,2]],
					['rh' => [1,2,4,5], 'lh' => [5,4,2,1]],
					['rh' => [2,4,1,2], 'lh' => [4,2,1,2]]
				]
			]
		],
		'D-' => [
			'mode' => 'major',
			'label' => 'Db major',
			'notes' => ['d-', 'e-', 'f', 'g-', 'a-', 'b-', 'c'],
			'scale' => 'C+',
			'arpeggio' => 'C+'
		],
		'D-m' => [
			'mode' => 'minor',
			'label' => 'Db minor',
			'notes' => ['d-', 'e-', 'f-', 'g-', 'a-', 'b--', 'c-'],
			'scale' => 'C+m',
			'arpeggio' => 'C+m'
		],
		'D' => [
			'mode' => 'major',
			'label' => 'D major',
			'notes' => ['d', 'e', 'f+', 'g', 'a', 'b', 'c+'],
			'scale' => [
				'diatonic' => [
					['rh' => [1,2,3,1,2,3,4,5], 'lh' => [5,4,3,2,1,3,2,1]]
				]
			],
			'arpeggio' => [
				'triad' => [
					['rh' => [1,2,3,5], 'lh' => [5,3,2,1]],
					['rh' => [2,1,2,4], 'lh' => [4,2,1,2]],
					['rh' => [1,2,4,5], 'lh' => [5,3,2,1]],
				]
			]
		],
		'Dm' => [
			'mode' => 'minor',
			'label' => 'D minor',
			'notes' => ['d', 'e', 'f', 'g', 'a', 'b-', 'c'],
			'scale' => [
				'diatonic' => [
					['rh' => [1,2,3,1,2,3,4,5], 'lh' => [5,4,3,2,1,3,2,1]]
				]
			],
			'arpeggio' => [
				'triad' => [
					['rh' => [1,2,3,5], 'lh' => [5,4,2,1]],
					['rh' => [1,2,4,5], 'lh' => [5,4,2,1]],
					['rh' => [1,2,4,5], 'lh' => [5,3,2,1]],
				]
			]
		],
		'D+' => [
			'mode' => 'major',
			'label' => 'D# major',
			'notes' => ['d+', 'e+', 'f++', 'g+', 'a+', 'b+', 'c++'],
			'scale' => [
				'diatonic' => [
					['rh' => [2,1,2,3,4,1,2,3], 'lh' => [3,2,1,4,3,2,1,3]]
				]
			],
			'arpeggio' => [
				'triad' => [
					['rh' => [2,1,2,4], 'lh' => [2,1,4,2]],
					['rh' => [1,2,4,5], 'lh' => [5,4,2,1]],
					['rh' => [2,4,1,2], 'lh' => [4,2,1,2]],
				]
			]
		],
		'D+m' => [
			'mode' => 'minor',
			'label' => 'D# minor',
			'notes' => ['d+', 'e+', 'f+', 'g+', 'a+', 'b', 'c+'],
			'scale' => [
				'diatonic' => [
					['rh' => [2,1,2,3,4,1,2,3], 'lh' => [2,1,4,3,2,1,3,2]]
				]
			],
			'arpeggio' => [
				'triad' => [
					['rh' => [1,2,3,5], 'lh' => [5,4,2,1]],
					['rh' => [1,2,4,5], 'lh' => [5,4,2,1]],
					['rh' => [1,2,3,5], 'lh' => [5,3,2,1]],
				]
			]
		],
		'E-' => [
			'mode' => 'major',
			'label' => 'E- major',
			'notes' => ['e-', 'f', 'g', 'a-', 'b-', 'c', 'd'],
			'scale' => 'D+',
			'arpeggio' => 'D+'
		],
		'E-m' => [
			'mode' => 'minor',
			'label' => 'Eb minor',
			'notes' => ['e-', 'f', 'g-', 'a-', 'b-', 'c-', 'd-'],
			'scale' => 'D+m',
			'arpeggio' => 'D+m'
		],
		'E' => [
			'mode' => 'major',
			'label' => 'E major',
			'notes' => ['e', 'f+', 'g+', 'a', 'b', 'c+', 'd+'],
			'scale' => [
				'diatonic' => [
					['rh' => [1,2,3,1,2,3,4,5], 'lh' => [5,4,3,2,1,3,2,1]]
				]
			],
			'arpeggio' => [
				'triad' => [
					['rh' => [1,2,3,5], 'lh' => [5,3,2,1]],
					['rh' => [2,1,2,4], 'lh' => [4,2,1,2]],
					['rh' => [1,2,4,5], 'lh' => [5,3,2,1]],
				]
			]
		],
		'Em' => [
			'mode' => 'minor',
			'label' => 'E minor',
			'notes' => ['e', 'f+', 'g', 'a', 'b', 'c', 'd'],
			'scale' => [
				'diatonic' => [
					['rh' => [1,2,3,1,2,3,4,5], 'lh' => [5,4,3,2,1,3,2,1]]
				]
			],
			'arpeggio' => [
				'triad' => [
					['rh' => [1,2,3,5], 'lh' => [5,4,2,1]],
					['rh' => [1,2,4,5], 'lh' => [5,4,2,1]],
					['rh' => [1,2,4,5], 'lh' => [5,3,2,1]],
				]
			]
		],
		'E+' => [
			'mode' => 'major',
			'label' => 'E# major',
			'notes' => ['e+', 'f++', 'g++', 'a+', 'b+', 'c++', 'd++'],
			'scale' => 'F',
			'arpeggio' => 'F'
		],
		'E+m' => [
			'mode' => 'minor',
			'label' => 'E# minor',
			'notes' => ['e+', 'f++', 'g+', 'a+', 'b+', 'c+', 'd+'],
			'scale' => 'Fm',
			'arpeggio' => 'Fm'
		],
		'F-' => [
			'mode' => 'major',
			'label' => 'Fb major',
			'notes' => ['f-', 'g-', 'a-', 'b--', 'c-', 'd-', 'e-'],
			'scale' => 'E',
			'arpeggio' => 'E'
		],
		'F-m' => [
			'mode' => 'minor',
			'label' => 'Fb minor',
			'notes' => ['f-', 'g-', 'a--', 'b--', 'c-', 'd--', 'e--'],
			'scale' => 'Em',
			'arpeggio' => 'Em'
		],
		'F' => [
			'mode' => 'major',
			'label' => 'F major',
			'notes' => ['f', 'g', 'a', 'b-', 'c', 'd', 'e'],
			'scale' => [
				'diatonic' => [
					['rh' => [1,2,3,4,1,2,3,4], 'lh' => [5,4,3,2,1,3,2,1]]
				]
			],
			'arpeggio' => [
				'triad' => [
					['rh' => [1,2,3,5], 'lh' => [5,4,2,1]],
					['rh' => [1,2,4,5], 'lh' => [5,4,2,1]],
					['rh' => [1,2,4,5], 'lh' => [5,3,2,1]],
				]
			]
		],
		'Fm' => [
			'mode' => 'minor',
			'label' => 'F minor',
			'notes' => ['f', 'g', 'a-', 'b-', 'c', 'd-', 'e-'],
			'scale' => [
				'diatonic' => [
					['rh' => [1,2,3,4,1,2,3,4], 'lh' => [5,4,3,2,1,3,2,1]]
				]
			],
			'arpeggio' => [
				'triad' => [
					['rh' => [1,2,3,5], 'lh' => [5,4,2,1]],
					['rh' => [2,1,2,3], 'lh' => [4,2,1,2]],
					['rh' => [1,2,3,5], 'lh' => [5,3,2,1]],
				]
			]
		],
		'F+' => [
			'mode' => 'major',
			'label' => 'F# major',
			'notes' => ['f+', 'g+', 'a+', 'b', 'c+', 'd+', 'e+'],
			'scale' => [
				'diatonic' => [
					['rh' => [2,3,4,1,2,3,1,2], 'lh' => [4,3,2,1,3,2,1,2]]
				]
			],
			'arpeggio' => [
				'triad' => [
					['rh' => [1,2,3,5], 'lh' => [5,4,2,1]],
					['rh' => [1,2,4,5], 'lh' => [5,4,2,1]],
					['rh' => [1,2,4,5], 'lh' => [5,3,2,1]],
				]
			]
		],
		'F+m' => [
			'mode' => 'minor',
			'label' => 'F# minor',
			'notes' => ['f+', 'g+', 'a', 'b', 'c+', 'd', 'e'],
			'scale' => [
				'diatonic' => [
					['rh' => [2,3,1,2,3,1,2,3], 'lh' => [4,3,2,1,3,2,1,2]]
				]
			],
			'arpeggio' => [
				'triad' => [
					['rh' => [2,1,2,4], 'lh' => [2,1,4,2]],
					['rh' => [1,2,4,5], 'lh' => [5,4,2,1]],
					['rh' => [2,4,1,2], 'lh' => [4,2,1,2]]
				]
			]
		],
		'G-' => [
			'mode' => 'major',
			'label' => 'Gb major',
			'notes' => ['g-', 'a-', 'b-', 'c-', 'd-', 'e-', 'f'],
			'scale' => 'F+',
			'arpeggio' => 'F+'
		],
		'G-m' => [
			'mode' => 'minor',
			'label' => 'Gb minor',
			'notes' => ['g-', 'a-', 'b--', 'c-', 'd-', 'e--', 'f-'],
			'scale' => 'F+m',
			'arpeggio' => 'F+m'
		],
		'G' => [
			'mode' => 'major',
			'label' => 'G major',
			'notes' => ['g', 'a', 'b', 'c', 'd', 'e', 'f+'],
			'scale' => 'C',
			'arpeggio' => 'C'
		],
		'Gm' => [
			'mode' => 'minor',
			'label' => 'G minor',
			'notes' => ['g', 'a', 'b-', 'c', 'd', 'e-', 'f'],
			'scale' => 'Cm',
			'arpeggio' => 'Cm'
		],
		'G+' => [
			'mode' => 'major',
			'label' => 'G# major',
			'notes' => ['g+', 'a+', 'b+', 'c', 'd+', 'e+', 'f++'],
			'scale' => 'A-',
			'arpeggio' => 'A-'
		],
		'G+m' => [
			'mode' => 'minor',
			'label' => 'G# minor',
			'notes' => ['g+', 'a+', 'b', 'c+', 'd+', 'e', 'f+'],
			'scale' => 'A-m',
			'arpeggio' => 'A-m'
		],
	];

	public function getKeys()
	{
		return $this->keys;
	}
}
