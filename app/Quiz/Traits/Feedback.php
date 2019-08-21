<?php

namespace App\Quiz\Traits;

trait Feedback
{
    protected $feedbacks = [
        'Hm, that didn\'t go very well... It\'s time to hit the textbooks! Definitely try again after you study a bit more:)', 
        'Not too bad, but there\'s plenty of room for improvement. We bet that next time you\'ll do better, <u>keep it up</u>!',
        'Well done, you got many correct answers. We recommend that you review the ones you missed and soon you\'ll be an expert!',
        'That was great, you almost nailed it! Don\'t stop studying though, as the saying goes: <u>practice makes perfect</u>.',
        'Wow, <strong>you\'re an expert</strong>! That was the perfect score, either this was too easy or you\'re just too smart. Congrats:)'];

    protected $gifs = [
    	['xUOxfolJrVBce4RNAI', '3og0INyCmHlNylks9O', '3o7btT1T9qpQZWhNlK'],
    	['xTkcExqBx71OapB4jK', '26AHJpsEO2WZImTpC', 'zYEg3iFhP7Ily'],
    	['mgqefqwSbToPe', 'xTiQyBOIQe5cgiyUPS', '4PSEQpvV5wUpnmpP1l'],
    	['3oEjI5VtIhHvK37WYo', '14udF3WUwwGMaA', '11sBLVxNs7v6WA'],
    	['3oz8xPIblfyMdZwM8w', '8BJJN8RHqHbb2', 'aWPGuTlDqq2yc']
    ];

    public function gif($index)
    {
    	$id = randval($this->gifs[$index]);

    	return "https://media.giphy.com/media/{$id}/giphy.gif";
    }
}
