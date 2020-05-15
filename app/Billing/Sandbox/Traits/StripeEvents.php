<?php

namespace App\Billing\Sandbox\Traits;

trait StripeEvents
{
	public function subscriptionRenewed()
	{
		return [
		  "type" => "customer.subscription.updated",
		  "data" => [
		    "object" => [
		      "cancel_at" => null,
		      "cancel_at_period_end" => false,
		      "canceled_at" => null,
		      "current_period_end" => 1592017411,
		      "customer" => $this->customerId,
		      "ended_at" => null,
		      "pause_collection" => null,
		      "plan" => [
		        "id" => "monthly",
		      ],
		      "status" => "active",
		    ],
		  ]
		];
	}

	public function subscriptionDidNotRenew()
	{
		return [
		  "type" => "customer.subscription.updated",
		  "data" => [
		    "object" => [
		      "cancel_at" => null,
		      "cancel_at_period_end" => false,
		      "canceled_at" => null,
		      "current_period_end" => 1592017411,
		      "customer" => $this->customerId,
		      "ended_at" => now()->timestamp,
		      "pause_collection" => null,
		      "plan" => [
		        "id" => "monthly",
		      ],
		      "status" => "canceled",
		    ],
		  ]
		];
	}

	public function subscriptionWillNotRenew()
	{
		return [
		  "type" => "customer.subscription.updated",
		  "data" => [
		    "object" => [
		      "cancel_at" => now()->addWeek()->timestamp,
		      "cancel_at_period_end" => true,
		      "canceled_at" => now()->timestamp,
		      "current_period_end" => now()->addWeek()->timestamp,
		      "customer" => $this->customerId,
		      "ended_at" => null,
		      "pause_collection" => null,
		      "plan" => [
		        "id" => "monthly",
		      ],
		      "status" => "trialing",
		    ],
		  ]
		];
	}

	public function subscriptionPaused()
	{
		return [
		  "type" => "customer.subscription.updated",
		  "data" => [
		    "object" => [
		      "cancel_at" => null,
		      "cancel_at_period_end" => null,
		      "canceled_at" => null,
		      "current_period_end" => 1592017411,
		      "customer" => $this->customerId,
		      "ended_at" => null,
		      "pause_collection" => [
		      	"behavior" => "void",
		      	"resumes_at" => null
		      ],
		      "plan" => [
		        "id" => "monthly",
		      ],
		      "status" => "active",
		    ],
		  ]
		];
	}

	public function subscriptionResumed()
	{
		return [
		  "type" => "customer.subscription.updated",
		  "data" => [
		    "object" => [
		      "cancel_at" => null,
		      "cancel_at_period_end" => null,
		      "canceled_at" => null,
		      "current_period_end" => 1592017411,
		      "customer" => $this->customerId,
		      "ended_at" => null,
		      "pause_collection" => null,
		      "plan" => [
		        "id" => "monthly",
		      ],
		      "status" => "active",
		    ],
		  ]
		];
	}

	public function subscriptionDeleted() {
		return [
		  "type" => "customer.subscription.deleted",
		  "data" => [
		    "object" => [
		      "cancel_at" => null,
		      "cancel_at_period_end" => false,
		      "ended_at" => now()->timestamp,
		      "customer" => $this->customerId,
		      "canceled_at" => now()->timestamp,
		      "status" => "canceled",
		      "plan" => [
		        "id" => "monthly",
		      ],
		      "pause_collection" => null,
		    ],
		  ]
		];
	}

	public function cardUpdated()
	{
		return [
			"data" => [
				"object" => [
					"id" => "card_1GiQH2GQzApYr7LG9EOmndIO",
					"brand" => "MasterCard",
					"customer" => $this->customerId,
					"last4" => "4444",
				]
			],
			"type" => "customer.source.created"
		];
	}

	public function planChanged()
	{
		return [
		  "type" => "customer.subscription.updated",
		  "data" => [
		    "object" => [
		      "cancel_at" => null,
		      "cancel_at_period_end" => false,
		      "canceled_at" => null,
		      "current_period_end" => 1592017411,
		      "customer" => $this->customerId,
		      "ended_at" => null,
		      "pause_collection" => null,
		      "plan" => [
		        "id" => "yearly",
		      ],
		      "status" => "active",
		    ],
		  ]
		];
	}

	public function chargeSucceeded()
	{
		return [
		  "type" => "charge.succeeded",
		  "data" => [
		    "object" => [
		      "id" => "ch_00000000000000",
		      "amount" => 100,
		      "amount_refunded" => 0,
		      "customer" => $this->customerId,
		    ]
		  ]
		];
	}
}
