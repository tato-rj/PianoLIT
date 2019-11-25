<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Piece;

class FixAppleMusicLinks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pianolit:fix-itunes';

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
        foreach (Piece::all() as $piece) {
            $array = $piece->itunes_array;

            if ($array) {
                foreach ($array as $index => $itunes) {
                    $array[$index]['link'] = str_replace('itms', 'https', $itunes['link']);
                    $array[$index]['link'] = str_replace('itunes.', 'music.', $itunes['link']);
                }
            }

            $piece->update(['itunes' => serialize($array)]);
        }
    }
}
