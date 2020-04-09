<?php

namespace App\Traits;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use App\Events\Memberships\NewTrial;
use App\Services\Apple\Sandbox\Membership as FakeMembership;

trait HasMembership
{
    public function subscribe(Request $request)
    {
        $json = $this->callApple($request->receipt_data, $request->password);

        $response = json_decode($json);

        try {
            $latest_receipt = $response->receipt->in_app[0];

            $record = $this->membership()->create([
                'plan' => $latest_receipt->product_id,
                'latest_receipt' => $request->receipt_data,
                'latest_receipt_info' => json_encode($latest_receipt),
                'password' => $request->password,
                'renews_at' => carbon($latest_receipt->expires_date)->timezone(config('app.timezone')),
                'validated_at' => now()
            ]);            
        } catch (\Exception $e) {
            $record = $this->membership()->create([
                'plan' => null,
                'latest_receipt' => $request->receipt_data,
                'latest_receipt_info' => null,
                'password' => $request->password,
                'renews_at' => null,
                'validated_at' => now()
            ]);
        }

        event(new NewTrial($this));

        return $record;
    }

    public function getStatus($callApple = false)
    {
        if ($this->super_user)
            return 'active';
        
        if (! $this->membership()->exists())
            return 'expired';

        if (! $this->membership->isExpired())
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
            : $client->post('https://buy.itunes.apple.com/verifyReceipt', ['body' => $payload])->getBody();

        return $response;
    }

    public function cleanReceipt($request)
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
    
    public function scopeExpired($query)
    {
        $users = $query->has('membership')->get();

        foreach ($users as $index => $user) {
            if (! $user->membership->isExpired())
                $users->forget($index);
        }

        return $users;
    }

    public function getIsOnTrialAttribute()
    {
        if (! $this->membership()->exists() || $this->membership->isExpired())
            return false;

        $diff = $this->membership->created_at->diffInDays($this->membership->renews_at);

        return $diff <= 7;
    }

    public function statusElements()
    {
        // THIS IS JILL, THE USER WHO SIGNED UP FOR THE YEAR
        // if ($this->id == 287)
        //     return view('admin.components.users.status.member');

        if ($this->super_user)
            return ['icon' => 'user-shield', 'color' => 'blue', 'label' => 'Super user'];

        if (! $this->membership)
            return ['icon' => 'ghost', 'color' => 'grey', 'label' => 'Guest'];

        if ($this->membership->isExpired())
            return ['icon' => 'credit-card', 'color' => 'grey', 'label' => 'Expired'];

        if ($this->isOnTrial)
            return ['icon' => 'credit-card', 'color' => 'yellow', 'label' => 'On trial'];

        return ['icon' => 'credit-card', 'color' => 'green', 'label' => 'Member (' . $this->membership->plan_name . ')'];
    }
}
