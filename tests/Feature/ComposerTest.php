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
        Composer::truncate();
        $request = make(Composer::class);

        $this->signIn();

        $this->post(route('admin.composers.store'), [
            'name' => $request->name,
            'biography' => $request->biography,
            'cover_image' => UploadedFile::fake()->image('file.jpg'),
            'gender' => $request->gender,
            'curiosity' => $request->curiosity,
            'period' => $request->period,
            'country_id' => $request->country_id,
            'is_famous' => $request->is_famous,
            'is_pedagogical' => $request->is_pedagogical,
            'date_of_birth' => $request->date_of_birth,
            'date_of_death' => $request->date_of_death,
        ]);

        $composer = Composer::first();

        $this->assertDatabaseHas('composers', ['name' => $composer->name]);

        \Storage::disk('public')->assertExists($composer->cover_path);
    }

    /** @test */
    public function an_admin_can_edit_a_composer()
    {
        $request = make(Composer::class)->toArray();
        $request['cover_image'] =  UploadedFile::fake()->image('cover.jpg');

        $this->signIn();

        $this->patch(route('admin.composers.update', $this->composer->id), $request);

        $this->assertEquals($this->composer->fresh()->name, $request['name']);
    }

    /** @test */
    public function an_admin_can_delete_a_composer()
    {
        $composerId = $this->composer->id;
        $cover = $this->composer->cover_path;

        $this->signIn();

        $this->delete(route('admin.composers.destroy', $this->composer->id));

        $this->assertDatabaseMissing('composers', ['id' => $composerId]);
        \Storage::disk('public')->assertMissing($cover);
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
