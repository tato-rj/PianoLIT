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
        $tutorial = Tutorial::latest()->first();

        try {
            $response = Http::get($tutorial->video_url);
            if ($response->successful()) {
                $this->info($tutorial->piece->name . ": the URL returned a 200 status code.");
            } else {
                $this->warn($tutorial->piece->name . ": the URL returned a code " . $response->status());
            }
        } catch (\Exception $e) {
            $this->error("An error occurred while pinging the URL: " . $e->getMessage());
        }
    }
}
