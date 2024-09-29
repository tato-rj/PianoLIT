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
        $tutorial = Tutorial::whereIn('type', ['synthesia', 'performance'])->take(2)->get()->last();

// dd($tutorial->piece_id);
        $url = 'https://leftlaneapps.com/videouploader/fix';
$this->updateTutorial($tutorial);
        // try {
        //     $this->pingUrl($tutorial, $url);
        // } catch (\Exception $e) {
        //     $this->error("An error occurred while pinging the URL: " . $e->getMessage());
        // }
    }

    public function pingUrl(Tutorial $tutorial, $url)
    {
        $oldPath = str_replace('https://leftlaneapps.com/storage/', '', $tutorial->video_url);
        $newPath = 'videos/'.str_slug($tutorial->type).'/'.$tutorial->piece->id . '.mp4';

        $response = Http::post($url, [
            'secret' => env('VIDEO_UPLOADER_SECRET'),
            'old_path' => $oldPath,
            'new_path' => $newPath
        ]);

        if ($response->successful()) {
            $this->updateTutorial($tutorial, $newPath);

            $this->info($response->json()['message'] ?? null);
        } else {
            $this->warn('Something went wrong: '.$response->status());
            $this->warn($response->json()['message'] ?? null);
        }

        // $response = Http::get($tutorial->video_url);
        
        // $pieceName = $tutorial->type . ' for ' . $tutorial->piece->medium_name . ' (ID ' . $tutorial->piece->id . ')';

        // if ($response->successful()) {
        //     $this->info($pieceName . " success: status code 200");
        // } else {
        //     $this->warn($pieceName . " is missing the video: status code " . $response->status());
        // }
    }

    public function updateTutorial(Tutorial $tutorial, $newPath = null)
    {
        $newUrl = 'https://leftlaneapps.com/storage/'.$newPath;
        // $oldUrl = 'https://leftlaneapps.com/storage/'.str_slug($tutorial->piece->composer->name).'/'.$tutorial->filename.'.mp4';

        \DB::table('tutorials')
            ->where('id', $tutorial->id)
            ->update(['video_url' => $newUrl]);
    }
}
