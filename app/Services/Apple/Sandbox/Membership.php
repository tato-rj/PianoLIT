<?php

namespace App\Services\Apple\Sandbox;

use App\Services\Apple\Sandbox\Traits\FakeReceipt;

class Membership extends Sandbox
{
	use FakeReceipt;
	
	protected $receipt, $receipt_data, $password, $originalDate;

	public function withRequest()
	{
		$this->receipt_data = 'fake-receipt-data';

		$this->password = 'fake-password';

		return $this;
	}

	public function generate($valid = true)
	{
		$this->receipt['in_app'] = $this->makePurchases($valid);
		$notification = $this->notification('fake');

		if (empty($this->receipt['in_app']))
			return null;

		$response['status'] = 0;
		$response['receipt'] = $this->receipt;
		$response['latest_receipt'] = $notification['latest_receipt'];
		$response['latest_receipt_info'] = $notification['latest_receipt_info'];
		
		return json_encode($response);
	}
}
