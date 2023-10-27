<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\{User, FavoriteFolder};

class eScoreGenerated
{
    use Dispatchable, SerializesModels;

    public $folder, $user;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, FavoriteFolder $folder)
    {
        $this->folder = $folder;
        $this->user = $user;
    }
}
