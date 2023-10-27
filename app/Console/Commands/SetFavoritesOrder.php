<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\FavoriteFolder;

class SetFavoritesOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'favorites:order';

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
     * @return int
     */
    public function handle()
    {
        foreach (FavoriteFolder::all() as $folder) {
            $folder->sort();
        }

        $this->info('The order of favorites have been set.');
    }
}
