<?php

namespace App\Services\Apple\Sandbox\Traits;

trait FakeReceipt
{
	public function receipt($date)
	{
		$date->addDays(rand(1,6));

		return [
			"version_external_identifier" => 0,
			"request_date" => $date->setTimezone('Etc/GMT')->addSecond()->format($this->format),
			"request_date_pst" => $date->setTimezone('America/Los_Angeles')->addSecond()->format($this->format),
			"request_date_ms" => $date->setTimezone('Etc/GMT')->addSecond()->timestamp,
			"receipt_type" => "ProductionSandbox",
			"bundle_id" => "com.leftlaneapps.PianoLIT",
			"receipt_creation_date" => $date->setTimezone('Etc/GMT')->format($this->format),
			"receipt_creation_date_pst" => $date->setTimezone('America/Los_Angeles')->format($this->format),
			"receipt_creation_date_ms" => $date->setTimezone('Etc/GMT')->timestamp,
			"download_id" => 0,
			"adam_id" => 0,
			"app_item_id" => 0,
			"application_version" => "1",
			"original_purchase_date" => "2013-08-01 07:00:00 Etc\/GMT",
			"original_purchase_date_pst" => "2013-08-01 00:00:00 America\/Los_Angeles",
			"original_purchase_date_ms" => "1375340400000",
			"original_application_version" => "1.0",
			"cancellation_date" => null,
			"cancellation_reason" => null,
		];
	}

	public function purchase($date)
	{
		$expirationDate = $date->copy()->addMonth();

		return [
			"quantity" => 1,
			"product_id" => 'monthly',
			"transaction_id" => $this->randomNumber(16),
			"purchase_date" => $date->setTimezone('Etc/GMT')->format($this->format),
			"purchase_date_pst" => $date->setTimezone('America/Los_Angeles')->format($this->format),
			"purchase_date_ms" => $date->setTimezone('Etc/GMT')->timestamp,
			"original_purchase_date" => $this->receipt['receipt_creation_date'],
			"original_purchase_date_pst" => $this->receipt['receipt_creation_date_pst'],
			"original_purchase_date_ms" => $this->receipt['receipt_creation_date_ms'],
			"expires_date" => $expirationDate->setTimezone('Etc/GMT')->format($this->format),
			"expires_date_pst" => $expirationDate->setTimezone('America/Los_Angeles')->format($this->format),
			"expires_date_ms" => $expirationDate->setTimezone('Etc/GMT')->timestamp,
			"expiration_intent" => null,
			"is_in_billing_retry_period" => null,
			"web_order_line_item_id" => $this->randomNumber(16),
			"auto_renew_status" => 1,
			"price_consent_status" => 1,
			"original_transaction_id" => $this->originalTransactionId,
			"is_in_intro_offer_period" => false,
			"is_trial_period" => false,			
		];
	}

	public function notification($event)
	{
		$renew_status = in_array($event, ['cancel', 'did_change_renewal_pref']) ? false : true;
		$cancel_date = $event == 'cancel' ? now()->format($this->format) : null;

		return json_decode('{"latest_receipt":"ewoJInNpZ25hdHVyZSIgPSAiQXdzNnUvQjJzVkhsNzBFM3c5KzNjQTBTVVprOFpYRlVZNEtmYVBFYkxleENUVDNmQWZFRCtYYm1vUmc4bFFyQVdreEFGcjdqMkp0UTYxc0Era1c5RmxGOHBwQzJQZ2RwWW1raGkvTFUveUEycDB4NE9vZlFHaEtISkdCOG1HMndlbWtNd1hZUmZrdmdHbEFoVUlOOGVkSDYrbWNOcHRzRkZkSlJQbDNERURTUHRNb1NhaThoQi9hZmZoZnR3YWtwV3MvbHZHcEJWMmdYcHpGcFlUV2ZwcHppUUVLM0ZqTUpSalF0ejUxWVhiamh3M0JucS9MckVTR3puNWRnbi9aWUk1UWcrdTFrTVYwSldwMnhBc0pJN2VMMlRWWVZoQVJQeW1vU2M4NE1LaGtvejk3K09UMGZtbnY3T2Z3eUxWS29Ta0ZycjdIcExqc1Z0bmdwVDhnczdyTUFBQVdBTUlJRmZEQ0NCR1NnQXdJQkFnSUlEdXRYaCtlZUNZMHdEUVlKS29aSWh2Y05BUUVGQlFBd2daWXhDekFKQmdOVkJBWVRBbFZUTVJNd0VRWURWUVFLREFwQmNIQnNaU0JKYm1NdU1Td3dLZ1lEVlFRTERDTkJjSEJzWlNCWGIzSnNaSGRwWkdVZ1JHVjJaV3h2Y0dWeUlGSmxiR0YwYVc5dWN6RkVNRUlHQTFVRUF3dzdRWEJ3YkdVZ1YyOXliR1IzYVdSbElFUmxkbVZzYjNCbGNpQlNaV3hoZEdsdmJuTWdRMlZ5ZEdsbWFXTmhkR2x2YmlCQmRYUm9iM0pwZEhrd0hoY05NVFV4TVRFek1ESXhOVEE1V2hjTk1qTXdNakEzTWpFME9EUTNXakNCaVRFM01EVUdBMVVFQXd3dVRXRmpJRUZ3Y0NCVGRHOXlaU0JoYm1RZ2FWUjFibVZ6SUZOMGIzSmxJRkpsWTJWcGNIUWdVMmxuYm1sdVp6RXNNQ29HQTFVRUN3d2pRWEJ3YkdVZ1YyOXliR1IzYVdSbElFUmxkbVZzYjNCbGNpQlNaV3hoZEdsdmJuTXhFekFSQmdOVkJBb01Da0Z3Y0d4bElFbHVZeTR4Q3pBSkJnTlZCQVlUQWxWVE1JSUJJakFOQmdrcWhraUc5dzBCQVFFRkFBT0NBUThBTUlJQkNnS0NBUUVBcGMrQi9TV2lnVnZXaCswajJqTWNqdUlqd0tYRUpzczl4cC9zU2cxVmh2K2tBdGVYeWpsVWJYMS9zbFFZbmNRc1VuR09aSHVDem9tNlNkWUk1YlNJY2M4L1cwWXV4c1FkdUFPcFdLSUVQaUY0MWR1MzBJNFNqWU5NV3lwb041UEM4cjBleE5LaERFcFlVcXNTNCszZEg1Z1ZrRFV0d3N3U3lvMUlnZmRZZUZScjZJd3hOaDlLQmd4SFZQTTNrTGl5a29sOVg2U0ZTdUhBbk9DNnBMdUNsMlAwSzVQQi9UNXZ5c0gxUEttUFVockFKUXAyRHQ3K21mNy93bXYxVzE2c2MxRkpDRmFKekVPUXpJNkJBdENnbDdaY3NhRnBhWWVRRUdnbUpqbTRIUkJ6c0FwZHhYUFEzM1k3MkMzWmlCN2o3QWZQNG83UTAvb21WWUh2NGdOSkl3SURBUUFCbzRJQjF6Q0NBZE13UHdZSUt3WUJCUVVIQVFFRU16QXhNQzhHQ0NzR0FRVUZCekFCaGlOb2RIUndPaTh2YjJOemNDNWhjSEJzWlM1amIyMHZiMk56Y0RBekxYZDNaSEl3TkRBZEJnTlZIUTRFRmdRVWthU2MvTVIydDUrZ2l2Uk45WTgyWGUwckJJVXdEQVlEVlIwVEFRSC9CQUl3QURBZkJnTlZIU01FR0RBV2dCU0lKeGNKcWJZWVlJdnM2N3IyUjFuRlVsU2p0ekNDQVI0R0ExVWRJQVNDQVJVd2dnRVJNSUlCRFFZS0tvWklodmRqWkFVR0FUQ0IvakNCd3dZSUt3WUJCUVVIQWdJd2diWU1nYk5TWld4cFlXNWpaU0J2YmlCMGFHbHpJR05sY25ScFptbGpZWFJsSUdKNUlHRnVlU0J3WVhKMGVTQmhjM04xYldWeklHRmpZMlZ3ZEdGdVkyVWdiMllnZEdobElIUm9aVzRnWVhCd2JHbGpZV0pzWlNCemRHRnVaR0Z5WkNCMFpYSnRjeUJoYm1RZ1kyOXVaR2wwYVc5dWN5QnZaaUIxYzJVc0lHTmxjblJwWm1sallYUmxJSEJ2YkdsamVTQmhibVFnWTJWeWRHbG1hV05oZEdsdmJpQndjbUZqZEdsalpTQnpkR0YwWlcxbGJuUnpMakEyQmdnckJnRUZCUWNDQVJZcWFIUjBjRG92TDNkM2R5NWhjSEJzWlM1amIyMHZZMlZ5ZEdsbWFXTmhkR1ZoZFhSb2IzSnBkSGt2TUE0R0ExVWREd0VCL3dRRUF3SUhnREFRQmdvcWhraUc5Mk5rQmdzQkJBSUZBREFOQmdrcWhraUc5dzBCQVFVRkFBT0NBUUVBRGFZYjB5NDk0MXNyQjI1Q2xtelQ2SXhETUlKZjRGelJqYjY5RDcwYS9DV1MyNHlGdzRCWjMrUGkxeTRGRkt3TjI3YTQvdncxTG56THJSZHJqbjhmNUhlNXNXZVZ0Qk5lcGhtR2R2aGFJSlhuWTR3UGMvem83Y1lmcnBuNFpVaGNvT0FvT3NBUU55MjVvQVE1SDNPNXlBWDk4dDUvR2lvcWJpc0IvS0FnWE5ucmZTZW1NL2oxbU9DK1JOdXhUR2Y4YmdwUHllSUdxTktYODZlT2ExR2lXb1IxWmRFV0JHTGp3Vi8xQ0tuUGFObVNBTW5CakxQNGpRQmt1bGhnd0h5dmozWEthYmxiS3RZZGFHNllRdlZNcHpjWm04dzdISG9aUS9PamJiOUlZQVlNTnBJcjdONFl0UkhhTFNQUWp2eWdhWndYRzU2QWV6bEhSVEJoTDhjVHFBPT0iOwoJInB1cmNoYXNlLWluZm8iID0gImV3b0pJbTl5YVdkcGJtRnNMWEIxY21Ob1lYTmxMV1JoZEdVdGNITjBJaUE5SUNJeU1ERTRMVEE1TFRJMklESXdPalF3T2pRMUlFRnRaWEpwWTJFdlRHOXpYMEZ1WjJWc1pYTWlPd29KSW5GMVlXNTBhWFI1SWlBOUlDSXhJanNLQ1NKMWJtbHhkV1V0ZG1WdVpHOXlMV2xrWlc1MGFXWnBaWElpSUQwZ0lqWTVOME16UmpaR0xVUTRNRU10TkVReE1pMDVPVFZCTFRVd1JVWkJSVFpDTmpkR05TSTdDZ2tpYjNKcFoybHVZV3d0Y0hWeVkyaGhjMlV0WkdGMFpTMXRjeUlnUFNBaU1UVXpPREF4T1RZME5UQXdNQ0k3Q2draVpYaHdhWEpsY3kxa1lYUmxMV1p2Y20xaGRIUmxaQ0lnUFNBaU1qQXhPQzB3T1MweU55QXdNem8wTlRvME5TQkZkR012UjAxVUlqc0tDU0pwY3kxcGJpMXBiblJ5YnkxdlptWmxjaTF3WlhKcGIyUWlJRDBnSW1aaGJITmxJanNLQ1NKd2RYSmphR0Z6WlMxa1lYUmxMVzF6SWlBOUlDSXhOVE00TURFNU5qUTFNREF3SWpzS0NTSmxlSEJwY21WekxXUmhkR1V0Wm05eWJXRjBkR1ZrTFhCemRDSWdQU0FpTWpBeE9DMHdPUzB5TmlBeU1EbzBOVG8wTlNCQmJXVnlhV05oTDB4dmMxOUJibWRsYkdWeklqc0tDU0pwY3kxMGNtbGhiQzF3WlhKcGIyUWlJRDBnSW1aaGJITmxJanNLQ1NKcGRHVnRMV2xrSWlBOUlDSXhORE0xTnpBMk56Y3dJanNLQ1NKMWJtbHhkV1V0YVdSbGJuUnBabWxsY2lJZ1BTQWlNVGd5WmpVeU1EQXpZbVkxTkRneE9EWTRZakptWkRRNE5HVmpZbU13WVRNNVltTmtOekExWmlJN0Nna2liM0pwWjJsdVlXd3RkSEpoYm5OaFkzUnBiMjR0YVdRaUlEMGdJakV3TURBd01EQTBORGszTlRZek56a2lPd29KSW1WNGNHbHlaWE10WkdGMFpTSWdQU0FpTVRVek9EQXhPVGswTlRBd01DSTdDZ2tpZEhKaGJuTmhZM1JwYjI0dGFXUWlJRDBnSWpFd01EQXdNREEwTkRrM05UWXpOemtpT3dvSkltSjJjbk1pSUQwZ0lqRWlPd29KSW5kbFlpMXZjbVJsY2kxc2FXNWxMV2wwWlcwdGFXUWlJRDBnSWpFd01EQXdNREF3TkRBMU5USTJNVFlpT3dvSkluWmxjbk5wYjI0dFpYaDBaWEp1WVd3dGFXUmxiblJwWm1sbGNpSWdQU0FpTUNJN0Nna2lZbWxrSWlBOUlDSmpiMjB1YkdWbWRHeGhibVZoY0hCekxsQnBZVzV2VEVsVUlqc0tDU0p3Y205a2RXTjBMV2xrSWlBOUlDSXdNaUk3Q2draWNIVnlZMmhoYzJVdFpHRjBaU0lnUFNBaU1qQXhPQzB3T1MweU55QXdNem8wTURvME5TQkZkR012UjAxVUlqc0tDU0p3ZFhKamFHRnpaUzFrWVhSbExYQnpkQ0lnUFNBaU1qQXhPQzB3T1MweU5pQXlNRG8wTURvME5TQkJiV1Z5YVdOaEwweHZjMTlCYm1kbGJHVnpJanNLQ1NKdmNtbG5hVzVoYkMxd2RYSmphR0Z6WlMxa1lYUmxJaUE5SUNJeU1ERTRMVEE1TFRJM0lEQXpPalF3T2pRMUlFVjBZeTlIVFZRaU93cDkiOwoJImVudmlyb25tZW50IiA9ICJTYW5kYm94IjsKCSJwb2QiID0gIjEwMCI7Cgkic2lnbmluZy1zdGF0dXMiID0gIjAiOwp9","latest_receipt_info":{"original_purchase_date_pst":"2018-09-26 20:40:45 America\/Los_Angeles","quantity":"1","unique_vendor_identifier":"697C3F6F-D80C-4D12-995A-50EFAE6B67F5","original_purchase_date_ms":"1538019645000","expires_date_formatted":"2018-09-27 03:45:45 Etc\/GMT","is_in_intro_offer_period":"false","purchase_date_ms":"1538019645000","expires_date_formatted_pst":"2018-09-26 20:45:45 America\/Los_Angeles","is_trial_period":"false","item_id":"1435706770","unique_identifier":"182f52003bf5481868b2fd484ecbc0a39bcd705f","original_transaction_id":"'.$this->originalTransactionId.'","expires_date":"2018-09-30 19:19:47 Etc\/GMT","transaction_id":"1000000449756379","bvrs":"1","web_order_line_item_id":"1000000040552616","version_external_identifier":"0","bid":"com.leftlaneapps.PianoLIT","product_id":"02","purchase_date":"2018-09-27 03:40:45 Etc\/GMT","purchase_date_pst":"2018-09-26 20:40:45 America\/Los_Angeles","original_purchase_date":"2018-09-27 03:40:45 Etc\/GMT"},"environment":"Sandbox","auto_renew_status":"'.$renew_status.'","password":"0fb7f614fbf747b08de1af8d88b7c835","auto_renew_product_id":"02","notification_type":"'.strtoupper($event).'","cancellation_date":"'.$cancel_date.'"}', true);
	}

	public function makePurchases()
	{
		$array = [];

		$receiptDate = carbon($this->receipt['receipt_creation_date']);

		while ($receiptDate->lt(now())) {
			
			if ($this->trueByChance(0))
				break;

		    array_push($array, $this->purchase($receiptDate));

		    $receiptDate->addMonth();
		}

		return $array;
	}

	public function randomNumber($length) {
		$result = '';

		for($i = 0; $i < $length; $i++) {
			$result .= mt_rand(0, 9);
		}

		return $result;
	}

	public function trueByChance($number)
	{
		return mt_rand(0, 10) < $number;
	}
}
