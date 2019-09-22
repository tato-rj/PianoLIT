<?php

namespace Tests\Feature;

use App\User;
use Tests\AppTest;
use App\Services\Apple\Sandbox\Membership as AppleMembership;
class MembershipTest extends AppTest
{
    /** @test */
    public function a_user_can_subscribe()
    {
        $membership = new AppleMembership;

        $user = create(User::class);

        $this->assertFalse($user->membership()->exists());

        $this->postMembership($user, $membership);

        $this->assertTrue($user->membership()->exists());

        $this->assertEquals('active', $user->getStatus());
    }

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

    /** @test */
    public function an_admin_can_verify_the_status_of_all_memberships()
    {
        $this->signIn();

        $this->user->membership->update(['renews_at' => now()->copy()->subWeek()]);
        
        $this->assertNull($this->user->membership->validated_at);

        $this->get(route('admin.memberships.validate.all'));

        $this->assertNotNull($this->user->membership->fresh()->validated_at);
    }
}
