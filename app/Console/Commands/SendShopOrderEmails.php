<?php

namespace App\Console\Commands;

use App\Services\Shop\OrderEmails;
use Illuminate\Console\Command;

class SendShopOrderEmails extends Command
{
    protected $signature = 'shop:send-order-emails';
    protected $description = 'Send pending live merchandise confirmation and shipping emails';

    public function handle(OrderEmails $emails)
    {
        if (!app()->environment('production')) { $this->info('Local/test customer emails remain disabled.'); return 0; }
        $emails->deliverPending();
        $this->info('Pending shop emails processed.');
        return 0;
    }
}
