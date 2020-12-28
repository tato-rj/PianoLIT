<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\Apple\AppleValidator;
use App\Billing\Sources\{Apple, Stripe};
use App\Notifications\Memberships\AppleMembershipsValidated;
use App\Admin;

class ValidateAppleMemberships implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $users;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($users)
    {
        $this->users = $users;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->users as $user) {
            if ($user->hasMembershipWith(Apple::class)) {
                $request = (new AppleValidator)->verify($user->membership->source->latest_receipt, $user->membership->source->password);  

                try {              
                    $user->membership->source->validate($request);   
                } catch (\Exception $e) {
                    //
                }
            }
        }

        Admin::notifyAll(new AppleMembershipsValidated);
    }
}
