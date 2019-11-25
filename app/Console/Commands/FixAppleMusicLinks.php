<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Piece;

class FixDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pianolit:fix-database';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix or improve values across the database';

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
        // foreach (Piece::all() as $piece) {
        //     $array = $piece->itunes_array;

        //     if (is_array($array)) {
        //         foreach ($array as $index => $itunes) {
        //             $array[$index]['link'] = str_replace('itms', 'https', $array[$index]['link']);
        //             $array[$index]['link'] = str_replace('itunes.', 'music.', $array[$index]['link']);
        //         }
    
        //         $piece->update(['itunes' => serialize($array)]);
        //     }
        // }
    }
}
