<?php

namespace Tests\Feature;

use App\{Admin, Subscription};
use Tests\AppTest;
use App\Mail\Newsletter\Welcome as WelcomeToNewsletter;

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

        \Mail::assertNotQueued(WelcomeToNewsletter::class);
    }
}
