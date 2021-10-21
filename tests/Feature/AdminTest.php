<?php

namespace Tests\Feature;

use App\{Admin, Subscription, User, EmailList};
use Tests\AppTest;
use App\Mail\Newsletter\Welcome as WelcomeToNewsletter;
use App\Mail\SuperUserEmail;

class AdminTest extends AppTest
{
    /** @test */
    public function unauthorized_users_cannot_log_into_the_admin_section()
    {
        $this->expectException('Illuminate\Auth\AuthenticationException');

        $this->get(route('admin.home')); 
    }

    /** @test */
    public function a_manager_can_add_new_editors()
    {
        $this->signIn();

        $editor = make(Admin::class)->toArray();

        $originalCount = Admin::count();

        $this->post(route('admin.editors.store'), $editor);

        $this->assertEquals($originalCount + 1, Admin::count());
    }

    /** @test */
    public function a_manager_can_edit_their_own_profile()
    {
        $this->signIn();

        $this->patch(route('admin.editors.update', $this->admin->id), ['name' => 'Jane', 'email' => $this->admin->email]);

        $this->assertTrue($this->admin->fresh()->name == 'Jane');
    }

    /** @test */
    public function a_manager_can_delete_their_own_profile()
    {
        $this->signIn();

        $admin = $this->admin;
      
        $piece = $this->admin->pieces->first();

        $this->delete(route('admin.editors.destroy', $admin->id));

        $this->assertDatabaseMissing('admins', ['name' => $admin->name]);

        $this->assertNull($piece->creator);
    }

    /** @test */
    public function admins_can_remove_users()
    {
        $this->signIn();

        $id = $this->user->id;

        $this->delete(route('admin.users.destroy', $id));

        $this->assertDatabaseMissing('users', ['id' => $id]);
    }

    /** @test */
    public function admins_can_purge_a_users_account_along_with_all_its_logs()
    {
        $this->signIn($this->user);

        Subscription::createOrActivate($this->user, $notifyUser = false);

        $user_id = $this->user->id;
        $user_email = $this->user->email;
        $subscription_id = $this->user->subscription->id;

        $this->get(route('users.profile'));
        $this->get(route('home'));
        
        $this->assertRedisHas('user:'.auth()->user()->id.':web');
        $this->assertDatabaseHas('users', ['id' => $user_id]);
        $this->assertDatabaseHas('subscriptions', ['email' => $user_email]);
        $this->assertDatabaseHas('locations', ['user_id' => $user_id]);
        $this->assertDatabaseHas('email_list_subscription', ['subscription_id' => $subscription_id]);

        $this->signIn();

        $this->delete(route('admin.users.purge', $user_id));

        $this->assertRedisEmpty();
        $this->assertDatabaseMissing('users', ['id' => $user_id]);
        $this->assertDatabaseMissing('subscriptions', ['email' => $user_id]);
        $this->assertDatabaseMissing('locations', ['user_id' => $user_id]);
        $this->assertDatabaseMissing('email_list_subscription', ['subscription_id' => $user_id]);
    }

    /** @test */
    public function admins_can_subscribe_multiple_emails_at_the_same_time()
    {
        \Mail::fake();

        $this->signIn();

        Subscription::truncate();

        $this->post(route('admin.subscriptions.store', [
            'emails' => '  test1@email.com,   test2@email.com, test3@email.com ',
            'origin_url' => 'testing'
        ]));

        $this->assertEquals(3, Subscription::count());

        $this->assertTrue(EmailList::newsletter()->has('test1@email.com'));

        \Mail::assertNotQueued(WelcomeToNewsletter::class);
    }

    /** @test */
    public function guests_cannot_impersonate_users()
    {
        $this->expectException('Symfony\Component\HttpKernel\Exception\HttpException');

        $this->get(route('impersonate', $this->user))->assertStatus(403);
    }

    /** @test */
    public function users_cannot_impersonate_users()
    {
        $this->signIn(create(User::class));

        $this->expectException('Symfony\Component\HttpKernel\Exception\HttpException');

        $this->get(route('impersonate', $this->user))->assertStatus(403);
    }

    /** @test */
    public function admins_can_impersonate_users()
    {
        $this->signIn();

        $this->assertEquals(get_class(auth()->user()), get_class($this->admin));

        $this->get(route('impersonate', $this->user));

        $this->assertEquals(get_class(auth()->user()), get_class($this->user));
        $this->assertEquals(auth()->user()->email, $this->user->email);
    }

    /** @test */
    public function a_user_is_notified_when_an_admin_gives_the_account_super_user_status()
    {
        \Mail::fake();

        $this->signIn();

        $this->assertNull($this->user->super_user);

        $this->patch(route('admin.users.super-status', $this->user->id));

        $this->assertTrue($this->user->fresh()->super_user);

        \Mail::assertSent(SuperUserEmail::class);
    }
}
