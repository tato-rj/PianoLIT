<?php

namespace Tests\Feature;

use App\{Pianist, Admin};
use Tests\AppTest;

class PianistTest extends AppTest
{
    /** @test */
    public function an_admin_can_add_a_pianist()
    {
        $pianist = make(Pianist::class)->toArray();

        $this->signIn();

        $this->post(route('admin.pianists.store'), $pianist);

        $this->assertDatabaseHas('pianists', ['name' => $pianist['name']]);
    }

    /** @test */
    public function an_admin_can_edit_a_pianist()
    {
        $updatedPianist = make(Pianist::class)->toArray();

        $this->signIn();

        $this->patch(route('admin.pianists.update', $this->pianist->id), $updatedPianist);

        $this->assertEquals($this->pianist->fresh()->name, $updatedPianist['name']);
    }

    /** @test */
    public function an_admin_can_delete_a_composer()
    {
        $pianistId = $this->pianist->id;

        $this->signIn();

        $this->delete(route('admin.pianists.destroy', $this->pianist->id));

        $this->assertDatabaseMissing('pianists', ['id' => $pianistId]);
    }

    /** @test */
    public function unauthorized_admins_cannot_delete_a_pianist()
    {
        $this->expectException('Illuminate\Auth\Access\AuthorizationException');

        $admin = create(Admin::class, ['role' => 'unauthorized']);

        $this->signIn($admin);

        $this->delete(route('admin.pianists.destroy', $this->pianist->id));
    }
}
