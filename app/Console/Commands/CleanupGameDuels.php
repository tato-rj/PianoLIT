<?php
namespace App\Console\Commands;
use App\Games\Duels\DuelService;
use Illuminate\Console\Command;
class CleanupGameDuels extends Command
{
    protected $signature = 'games:cleanup-duels';
    protected $description = 'Expire abandoned game duels and release their invitation codes';
    public function handle(DuelService $duels)
    {
        $this->info($duels->cleanup().' duels closed');
        return 0;
    }
}
