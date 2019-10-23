<?php

namespace Tests\Feature;

use App\{Composer, Admin};
use Tests\AppTest;
use Illuminate\Http\UploadedFile;

class ComposerTest extends AppTest
{
    /** @test */
    public function an_admin_can_add_a_composer()
    {
        $composer = make(Composer::class);

        $this->signIn();

        $this->post(route('admin.composers.store'), [
            'name' => $composer->name,
            'biography' => $composer->biography,
            'cover' => UploadedFile::fake()->create('file.jpg'),
            'gender' => $composer->gender,
            'curiosity' => $composer->curiosity,
            'period' => $composer->period,
            'country_id' => $composer->country_id,
            'is_famous' => $composer->is_famous,
            'date_of_birth' => $composer->date_of_birth,
            'date_of_death' => $composer->date_of_death,
        ]);

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
