<?php

namespace Tests\Traits;

use App\Billing\Sandbox\StripeSandbox;
use App\Billing\Plan;

trait InteractsWithStripe
{
	public function postStripeMembership()
	{
        $this->post(route('webapp.membership.purchase',
            ['stripeToken' => (new StripeSandbox)->token(), 'plan' => create(Plan::class, ['name' => 'monthly'])]
        ));
	}
}
