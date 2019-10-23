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
    public function an_admin_can_verify_the_status_of_all_memberships()
    {
        $this->signIn();

        $this->user->membership->update(['renews_at' => now()->copy()->subWeek()]);
        dd('test');
        $this->assertNull($this->user->membership->validated_at);

        $this->get(route('admin.memberships.validate.all'));

        $this->assertNotNull($this->user->membership->fresh()->validated_at);
    }
}
