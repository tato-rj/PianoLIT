<?php

namespace Tests\Feature;

use Tests\AppTest;
use App\StudioPolicy;
use App\Mail\NewStudioPolicyEmail;
use App\Notifications\NewStudioPolicyNotification;

class StudioPolicyTest extends AppTest
{
    public function setUp() : void
    {
        parent::setUp();
        
        $this->studio_policy = create(StudioPolicy::class, ['user_id' => $this->user->id]);
    }

    /** @test */
    public function a_user_can_create_a_studio_policy()
    {
        $this->signIn($this->user);

        $data = ['name' => auth()->user()->full_name];

        $this->post(route('users.studio-policies.store'), $data);

        $this->assertDatabaseHas('studio_policies', ['data' => json_encode($data)]);
    }

    /** @test */
    public function a_user_and_admins_are_notified_when_a_policy_is_generated()
    {
        \Mail::fake();
        \Notification::fake();

        $this->signIn($this->user);

        $this->post(route('users.studio-policies.store'), make(StudioPolicy::class)->data);

        \Mail::assertQueued(NewStudioPolicyEmail::class);

        \Notification::assertSentTo($this->admin, NewStudioPolicyNotification::class);
    }

    /** @test */
    public function a_user_can_update_a_studio_policy()
    {
        $this->signIn($this->user);

        $data = ['name' => 'New name'];

        $this->patch(route('users.studio-policies.update', $this->studio_policy->id), $data);

        $this->assertDatabaseHas('studio_policies', ['data' => json_encode($data)]);
    }

    /** @test */
    public function a_user_can_delete_a_studio_policy()
    {
        $this->signIn($this->user);

        $this->assertEquals(1, $this->user->studioPolicies->count());

        $this->delete(route('users.studio-policies.destroy', $this->studio_policy->id));

        $this->assertEquals(0, $this->user->fresh()->studioPolicies->count());
    }

    /** @test */
    public function users_can_only_update_their_own_studio_policy()
    {
        $this->expectException('Illuminate\Auth\Access\AuthorizationException');

        $this->signIn($this->user);

        $data = ['name' => 'New name'];

        $this->patch(route('users.studio-policies.update', create(StudioPolicy::class)->id), $data);
    }

    /** @test */
    public function users_can_only_delete_their_own_studio_policy()
    {
        $this->expectException('Illuminate\Auth\Access\AuthorizationException');

        $this->signIn($this->user);

        $this->delete(route('users.studio-policies.destroy', create(StudioPolicy::class)->id));
    }
}
