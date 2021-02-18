<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\{Favorite, FavoriteFolder};

class CreateDefaultFolders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'temp:default-folders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a folder called favorite for every user with old favorited pieces';

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
        if ($this->confirm('This will create a folder called favorite for every user with old favorited pieces. Are you sure?')) {
            $favorites = Favorite::whereNull('favorite_folder_id')->get();

            foreach($favorites as $favorite) {
                $folder = FavoriteFolder::firstOrCreate([
                    'user_id' => $favorite->user_id,
                    'name' => 'Favorites',
                    'is_default' => true
                ]);

                try {
                    $favorite->update(['favorite_folder_id' => $folder->id]);
                } catch (\Exception $e) {

                }
            }

            return $this->info('All set! We have updated ' . $favorites->count() . ' favorites');
        }
    }
}
