<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Tutorial;

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
        // https://leftlaneapps.com/storage/dmitry-kabalevsky/op27-no1-01.mp4
        $tutorial = Tutorial::latest()->first();

        $tutorial->update(
            ['video_url' => 'https://leftlaneapps.com/storage/videos/performance/359.mp4']
        );

        $$this->info($tutorial);

        // $this->info('Checking ' . $tutorial->video_url . '...');

        // $pieceName = $tutorial->type . ' for ' . $tutorial->piece->medium_name . ' (ID ' . $tutorial->piece->id . ')';

        // try {
        //     $response = Http::get($tutorial->video_url);
        //     if ($response->successful()) {
        //         $this->info($pieceName . " success: status code 200");
        //     } else {
        //         $this->warn($pieceName . " is missing the video: status code " . $response->status());
        //     }
        // } catch (\Exception $e) {
        //     $this->error("An error occurred while pinging the URL: " . $e->getMessage());
        // }
    }
}
