<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Piece;

class FixVideos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'videos:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks if a video exists';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $piece = Piece::find(569);
        $this->info($piece->name);
    }
}
