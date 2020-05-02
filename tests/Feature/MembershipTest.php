<?php

namespace Tests\Feature;

use App\User;
use Tests\AppTest;
use App\Events\Memberships\NewTrial;
use App\Services\Apple\Sandbox\Membership as AppleMembership;
use App\Notifications\Memberships\NewTrialNotification;

class MembershipTest extends AppTest
{
    /** @test */
    public function a_user_can_subscribe()
    {
        $user = create(User::class);

        $this->assertFalse($user->membership()->exists());

        $this->postMembership($user, new AppleMembership);

        $this->assertTrue($user->membership()->exists());

        $this->assertEquals('active', $user->getStatus());
    }

    /** @test */
    public function admins_are_notified_when_a_user_starts_a_trial()
    {
        \Notification::fake();

        $this->postMembership(create(User::class), new AppleMembership);

        \Notification::assertSentTo($this->admin, NewTrialNotification::class);
    }

    /** @test */
    public function an_admin_can_verify_the_status_of_all_apple_memberships()
    {
        $this->signIn();

        $this->user->membership->source->update(['renews_at' => now()->copy()->subWeek()]);
        
        $this->assertNull($this->user->membership->source->validated_at);

        $this->get(route('admin.memberships.validate.all'));

        $this->assertNotNull($this->user->membership->fresh()->source->validated_at);
    }

    /** @test */
    public function an_admin_can_verify_the_status_of_a_specific_apple_member()
    {
        $this->signIn();

        $this->user->membership->source->update(['renews_at' => now()->copy()->subWeek()]);
        
        $this->assertNull($this->user->membership->source->validated_at);

        $this->post(route('admin.memberships.validate.user', ['user' => $this->user->id, 'user_id' => $this->user->id]));

        $this->assertNotNull($this->user->membership->source->fresh()->validated_at);
    }
}
