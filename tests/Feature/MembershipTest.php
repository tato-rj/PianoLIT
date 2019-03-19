<?php

namespace Tests\Feature;

use App\User;
use Tests\AppTest;

class MembershipTest extends AppTest
{
    /** @test */
    public function an_admin_can_extend_the_users_trial()
    {
        $this->signIn();

        $user = create(User::class);

        $oldTrialDate = $user->trial_ends_at;

        $this->patch(route('admin.users.update-trial', $user->id));

        $this->assertTrue($oldTrialDate->lt($user->fresh()->trial_ends_at));
    }

    /** @test */
    public function an_admin_can_restart_a_users_trial()
    {
        $this->signIn();

        $user = create(User::class, [
            'created_at' => now()->subWeeks(4),
            'trial_ends_at' => now()->subWeeks(3)
        ]);

        $this->assertEquals('expired', $user->getStatus());

        $this->patch(route('admin.users.update-trial', $user->id));

        $this->assertEquals('trial', $user->fresh()->getStatus());   
    }
}
