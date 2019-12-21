<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateVideos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'temp:update-videos';

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
        $pieces = \App\Piece::all();

        foreach ($pieces as $piece) {
            $videos = $piece->videos_array_raw;
            $newArray = [];
    
            if (is_array($videos)) {
                foreach ($videos as $video) {
                    array_push($newArray, ['title' => null, 'description' => null, 'filename' => $video]);
                }

                $piece->update(['videos' => serialize($newArray)]);
            }  
        }

        $this->info('Done!');
    }
}
