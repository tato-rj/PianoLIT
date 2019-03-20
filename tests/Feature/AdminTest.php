<?php

namespace Tests\Feature;

use App\Admin;
use Tests\AppTest;

class AdminTest extends AppTest
{
    /** @test */
    public function it_can_log_into_the_admin_page()
    {
        $this->signIn();

        $this->get(route('admin.home'))->assertSuccessful(); 
    }

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

        $this->assertEquals(1, Admin::count());

        $this->post(route('admin.editors.store'), $editor);

        $this->assertEquals(2, Admin::count());
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
}
