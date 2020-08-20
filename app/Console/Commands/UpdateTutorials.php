<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\{Piece, Tutorial};

class UpdateTutorials extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'temp:update-tutorials';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
     * @return mixed
     */
    public function handle()
    {
        $pieces = Piece::withVideos()->get();

        foreach ($pieces as $piece) {
            if ($videos = unserialize($piece->videos)) {
                foreach ($videos as $video) {
                    $piece->tutorials()->create([
                        'type' => $video['title'] ?? 'MISSING',
                        'description' => $video['description'] ?? 'MISSING',
                        'filename' => $video['filename'] ?? 'MISSING'
                    ]);                       
                }
            }            
        }

        return $this->info('All tutorials have been saved on the new table.');
    }
}
