<?php

namespace App\Services\Apple;

use GuzzleHttp\Client;
use App\Services\Apple\Sandbox\Membership as FakeMembership;

class AppleValidator
{
    public function verify($receipt_data, $password)
    {
        $client = new Client([
            'headers' => ['Content-Type' => 'application/json']
        ]);

        $payload = json_encode([
            'receipt-data' => $receipt_data,
            'password' => $password,
            'exclude-old-transactions' => false
        ]);

        $response = app()->environment() != 'production' 
            ? (new FakeMembership)->generate() 
            : $client->post('https://buy.itunes.apple.com/verifyReceipt', ['body' => $payload])->getBody();

        return $response;
    }

    public function clean($request)
    {
        $request = json_decode($request);

        foreach ($request->receipt as $field => $value) {
            if (preg_match('(pst|ms)', $field) === 1 || is_null($value))
                unset($request->receipt->$field);   
        }

        foreach ($request->latest_receipt_info as $receipt) {
            foreach ($receipt as $field => $value) {
                if (preg_match('(pst|ms)', $field) === 1 || is_null($value))
                    unset($receipt->$field);   
            }
        }

        return $request;
    }
}
