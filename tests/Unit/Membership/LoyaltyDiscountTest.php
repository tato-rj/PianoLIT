<?php

namespace Tests\Unit\Membership;

use App\Billing\LoyaltyDiscount;
use App\Merchandise\Purchase;
use Tests\AppTest;

class LoyaltyDiscountTest extends AppTest
{
	/** @test */
	public function it_knows_if_it_has_any_records_from_the_previous_month()
	{
		$this->withoutEvents();

		create(LoyaltyDiscount::class, [
			'purchase_id' => create(Purchase::class, ['user_id' => $this->user->id])->id
		]);
		
		create(LoyaltyDiscount::class, [
			'purchase_id' => create(Purchase::class, ['user_id' => $this->user->id])->id,
			'created_at' => now()->subWeek()
		]);

		create(LoyaltyDiscount::class, [
			'purchase_id' => create(Purchase::class, ['user_id' => $this->user->id])->id,
			'created_at' => now()->subMonth()
		]);

		$this->assertEquals(3, $this->user->loyaltyDiscounts()->count());

		$this->assertEquals(2, $this->user->loyaltyDiscounts()->lastMonth()->count());
	}
}
