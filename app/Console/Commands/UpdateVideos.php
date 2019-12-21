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
    protected $signature = 'update:videos';

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

            if (is_array($videos) && count($videos) > 0) {
                $videos[0]['title'] = 'Performance';
                $videos[0]['description'] = 'Watch a video recording of this piece';
            }

            $piece->update(['videos' => serialize($videos)]);
        }

        return $this->info('Done!');
    }
}
