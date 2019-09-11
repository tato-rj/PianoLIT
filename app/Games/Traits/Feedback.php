<?php

namespace App\Games\Traits;

trait Feedback
{
    protected $feedbacks = [
        'Hm, that didn\'t go very well... It\'s time to hit the textbooks! Definitely try again after you study a bit more:)', 
        'Not too bad, but there\'s plenty of room for improvement. We bet that next time you\'ll do better, <u>keep it up</u>!',
        'Well done, you got many correct answers. We recommend that you review the ones you missed and soon you\'ll be an expert!',
        'That was great, you almost nailed it! Don\'t stop studying though, as the saying goes: <u>practice makes perfect</u>.',
        'Wow, <strong>you\'re an expert</strong>! That was the perfect score, either this was too easy or you\'re just too smart. Congrats:)'
    ];

    protected $gifs = [
    	['xUOxfolJrVBce4RNAI', '3og0INyCmHlNylks9O', '3o7btT1T9qpQZWhNlK', '3ohs7KViF6rA4aan5u', '3oEjHKBjU7q1UGuM1i'],
    	['xTkcExqBx71OapB4jK', '26AHJpsEO2WZImTpC', 'zYEg3iFhP7Ily', 'ANbD1CCdA3iI8', '9Y5BbDSkSTiY8'],
    	['mgqefqwSbToPe', 'xTiQyBOIQe5cgiyUPS', '4PSEQpvV5wUpnmpP1l', 'GCvktC0KFy9l6', 'zhIDyICDn75xm'],
    	['3oEjI5VtIhHvK37WYo', '14udF3WUwwGMaA', '11sBLVxNs7v6WA', 'NEvPzZ8bd1V4Y', 'gcjmXVppGVhKw'],
    	['3oz8xPIblfyMdZwM8w', '8BJJN8RHqHbb2', 'aWPGuTlDqq2yc', '5p2wQFyu8GsFO', 'pa37AAGzKXoek']
    ];

    public function gif($index)
    {
    	$id = randval($this->gifs[$index]);

    	return "https://media.giphy.com/media/{$id}/giphy.gif";
    }

    public function getFeedback($score, $count)
    {
        $percentage = percentage($score, $count);
        $slots = [24, 49, 74, 99, 100];
        $feedback;

        foreach ($slots as $index => $slot) {
            if ($percentage <= $slot) {
                $gif = $this->gif($index);
                $feedback = $this->feedbacks[$index];
                break;  
            }
        }

        return ['gif' => $gif, 'sentence' => $feedback];
    }
}
