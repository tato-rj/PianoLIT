<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Piece;

class UpdateScoreNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'temp:update-scores';

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
        $pieces = Piece::whereNotNull('score_path')->get();

        foreach ($pieces as $piece) {
            $piece->renameScore();        
        }

        return $this->info('The scores have been updated');
    }
}
