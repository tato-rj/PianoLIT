<?php

namespace App\Traits;

use Illuminate\Http\Request;
use App\Events\Memberships\NewTrial;
use App\Services\Apple\AppleValidator;

trait HasMembership
{
    public function subscribe(Request $request)
    {
        $json = (new AppleValidator)->verify($request->receipt_data, $request->password);

        $response = json_decode($json);

        try {
            $latest_receipt = $response->receipt->in_app[0];

            $source = \App\Payments\Sources\Apple::create([
                'plan' => $latest_receipt->product_id,
                'latest_receipt' => $request->receipt_data,
                'latest_receipt_info' => json_encode($latest_receipt),
                'password' => $request->password,
                'renews_at' => carbon($latest_receipt->expires_date)->timezone(config('app.timezone')),
                'validated_at' => now()
            ]);

            $record = $this->membership()->create([
                'source_id' => $source->id,
                'source_type' => get_class($source)
            ]);
        } catch (\Exception $e) {
            $source = \App\Payments\Sources\Apple::create([
                'plan' => null,
                'latest_receipt' => $request->receipt_data,
                'latest_receipt_info' => null,
                'password' => $request->password,
                'renews_at' => null,
                'validated_at' => now()
            ]);
            
            $record = $this->membership()->create([
                'source_id' => $source->id,
                'source_type' => get_class($source)
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

        if (! $this->membership->source->isExpired())
            return 'active';

        if (! $callApple)
            return 'inactive';

        $request = (new AppleValidator)->verify($this->membership->source->latest_receipt, $this->membership->source->password);

        return $this->membership->source->validate($request);
    }

    public function isAuthorized()
    {
        return in_array($this->getStatus(), ['active', 'trial']);
    }

    // public function callApple($receipt_data, $password)
    // {
    //     $client = new Client([
    //         'headers' => ['Content-Type' => 'application/json']
    //     ]);

    //     $payload = json_encode([
    //         'receipt-data' => $receipt_data,
    //         'password' => $password,
    //         'exclude-old-transactions' => false
    //     ]);

    //     $response = app()->environment() != 'production' 
    //         ? (new FakeMembership)->generate() 
    //         : $client->post('https://buy.itunes.apple.com/verifyReceipt', ['body' => $payload])->getBody();

    //     return $response;
    // }

    // public function cleanReceipt($request)
    // {
    //     $request = json_decode($request);

    //     foreach ($request->receipt as $field => $value) {
    //         if (preg_match('(pst|ms)', $field) === 1 || is_null($value))
    //             unset($request->receipt->$field);   
    //     }

    //     foreach ($request->latest_receipt_info as $receipt) {
    //         foreach ($receipt as $field => $value) {
    //             if (preg_match('(pst|ms)', $field) === 1 || is_null($value))
    //                 unset($receipt->$field);   
    //         }
    //     }

    //     return $request;
    // }
    
    public function scopeExpired($query)
    {
        $users = $query->has('membership')->get();

        foreach ($users as $index => $user) {
            if (! $user->membership->source->isExpired())
                $users->forget($index);
        }

        return $users;
    }

    public function getIsOnTrialAttribute()
    {
        if (! $this->membership()->exists() || $this->membership->source->isExpired())
            return false;

        $diff = $this->membership->source->created_at->diffInDays($this->membership->source->renews_at);

        return $diff <= 7;
    }

    public function statusElements()
    {
        if ($this->super_user)
            return ['icon' => 'user-shield', 'color' => 'blue', 'label' => 'Super user'];

        if (! $this->membership)
            return ['icon' => 'ghost', 'color' => 'grey', 'label' => 'Guest'];

        if ($this->membership->source->isExpired())
            return ['icon' => 'credit-card', 'color' => 'grey', 'label' => 'Expired'];

        if ($this->isOnTrial)
            return ['icon' => 'credit-card', 'color' => 'yellow', 'label' => 'On trial'];

        return ['icon' => 'credit-card', 'color' => 'green', 'label' => 'Member (' . $this->membership->source->plan_name . ')'];
    }
}
