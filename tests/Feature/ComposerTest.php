<?php

namespace Tests\Feature;

use App\{Composer, Admin};
use Tests\AppTest;

class ComposerTest extends AppTest
{
    /** @test */
    public function an_admin_can_add_a_composer()
    {
        $composer = make(Composer::class)->toArray();

        $this->signIn();

        $this->post(route('admin.composers.store'), $composer);

        $this->assertDatabaseHas('composers', ['name' => $composer['name']]);
    }

    /** @test */
    public function an_admin_can_edit_a_composer()
    {
        $updatedComposer = make(Composer::class)->toArray();

        $this->signIn();

        $this->patch(route('admin.composers.update', $this->composer->id), $updatedComposer);

        $this->assertEquals($this->composer->fresh()->name, $updatedComposer['name']);
    }

    /** @test */
    public function an_admin_can_delete_a_composer()
    {
        $composerId = $this->composer->id;

        $this->signIn();

        $this->delete(route('admin.composers.destroy', $this->composer->id));

        $this->assertDatabaseMissing('composers', ['id' => $composerId]);
    }

    /** @test */
    public function unauthorized_admins_cannot_delete_a_composer()
    {
        $this->expectException('Illuminate\Auth\Access\AuthorizationException');

        $admin = create(Admin::class, ['role' => 'unauthorized']);

        $this->signIn($admin);

        $this->delete(route('admin.composers.destroy', $this->composer->id));
    }
}
