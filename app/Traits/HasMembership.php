<?php

namespace App\Traits;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use App\Services\Apple\Sandbox\Membership as FakeMembership;

trait HasMembership
{
    public function subscribe(Request $request)
    {
        $json = $this->callApple($request->receipt_data, $request->password);

        $response = json_decode($json);

        $latest_receipt = $response->receipt->in_app[0];

        $record = $this->membership()->create([
            'plan' => $latest_receipt->product_id,
            'latest_receipt' => $request->receipt_data,
            'latest_receipt_info' => json_encode($latest_receipt),
            'password' => $request->password,
            'renews_at' => Carbon::parse($latest_receipt->expires_date)->timezone(config('app.timezone')),
            'validated_at' => now()
        ]);

        $this->update(['trial_ends_at' => null]);

        return $record;
    }

    public function getStatus($callApple = false)
    {
        if ($this->super_user)
            return 'active';
        
        if (! $this->membership()->exists() && $this->trial_ends_at->gte(now()))
            return 'trial';

        if (! $this->membership()->exists() && $this->trial_ends_at->lt(now()))
            return 'expired';

        if (! $this->membership->expired())
            return 'active';

        if (! $callApple)
            return 'inactive';

        $request = $this->callApple($this->membership->latest_receipt, $this->membership->password);

        return $this->membership->validate($request);
    }

    public function callApple($receipt_data, $password)
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
            : $client->post('https://sandbox.itunes.apple.com/verifyReceipt', ['body' => $payload])->getBody();

        return $response;
    }

    public function scopeExpired($query)
    {
        $users = $query->has('membership')->get();

        foreach ($users as $index => $user) {
            if (! $user->membership->expired())
                $users->forget($index);
        }

        return $users;
    }
}
