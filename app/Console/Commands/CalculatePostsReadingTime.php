<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CalculatePostsReadingTime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'temp:post_time';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate posts reading time';

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
        foreach (\App\Blog\Post::all() as $post) {
            $post->update(['reading_time' => $post->calculateTime()]);
        }

        return 'All good!';
    }
}
