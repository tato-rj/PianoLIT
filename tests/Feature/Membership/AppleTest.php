<?php

namespace Tests\Feature\Membership;

use Tests\AppTest;
use Tests\Traits\BillingResources;
use App\User;
use App\Notifications\Memberships\NewTrialNotification;
use App\Notifications\Memberships\AppleMembershipsValidated;

class AppleTest extends AppTest
{
    use BillingResources;

    /** @test */
    public function a_user_can_subscribe_through_the_ios_app()
    {
        $user = create(User::class);

        $this->assertFalse($user->membership()->exists());

        $this->postAppleMembership($user);

        $this->assertTrue($user->membership()->exists());

        $this->assertEquals('active', $user->getStatus());
    }

    /** @test */
    public function admins_are_notified_when_a_user_starts_a_trial()
    {
        \Notification::fake();

        $this->postAppleMembership(create(User::class));

        \Notification::assertSentTo($this->admin, NewTrialNotification::class);
    }

    /** @test */
    public function an_admin_can_verify_the_status_of_all_apple_memberships()
    {
        \Notification::fake();

        $this->signIn();

        $this->appleUser->membership->source->update(['renews_at' => now()->copy()->subWeek()]);
        
        $this->assertNull($this->appleUser->membership->source->validated_at);

        $this->get(route('admin.memberships.validate.all'));

        $this->assertNotNull($this->appleUser->membership->fresh()->source->validated_at);

        \Notification::assertSentTo($this->admin, AppleMembershipsValidated::class);
    }

    /** @test */
    public function an_admin_can_verify_the_status_of_a_specific_apple_member()
    {
        $this->signIn();

        $this->appleUser->membership->source->update(['renews_at' => now()->copy()->subWeek()]);
        
        $this->assertNull($this->appleUser->membership->source->validated_at);

        $this->post(route('admin.memberships.validate.user', ['user' => $this->appleUser->id, 'user_id' => $this->appleUser->id]));

        $this->assertNotNull($this->appleUser->membership->source->fresh()->validated_at);
    }
}
