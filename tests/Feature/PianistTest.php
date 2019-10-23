<?php

namespace Tests\Feature;

use App\{Pianist, Admin};
use Illuminate\Http\UploadedFile;
use Tests\AppTest;

class PianistTest extends AppTest
{
    /** @test */
    public function an_admin_can_add_a_pianist()
    {
        $pianist = make(Pianist::class);
        $cover = UploadedFile::fake()->create('file.jpg');
        
        $this->signIn();

        $this->post(route('admin.pianists.store'), [
            'name' => $pianist->name,
            'cover' => $cover,
            'biography' => $pianist->biography,
            'country_id' => $pianist->country_id,
            'itunes_id' => $pianist->itunes_id,
            'date_of_birth' => $pianist->date_of_birth,
            'date_of_death' => $pianist->date_of_birth,
        ]);

        $this->assertDatabaseHas('pianists', ['name' => $pianist->name]);
    }

    /** @test */
    public function an_admin_can_edit_a_pianist()
    {
        $updatedPianist = make(Pianist::class)->toArray();

        $this->signIn();

        $this->patch(route('admin.pianists.update', $this->pianist->slug), $updatedPianist);

        $this->assertEquals($this->pianist->fresh()->name, $updatedPianist['name']);
    }

    /** @test */
    public function an_admin_can_delete_a_composer()
    {
        $pianistId = $this->pianist->id;

        $this->signIn();

        $this->delete(route('admin.pianists.destroy', $this->pianist->slug));

        $this->assertDatabaseMissing('pianists', ['id' => $pianistId]);
    }

    /** @test */
    public function unauthorized_admins_cannot_delete_a_pianist()
    {
        $this->expectException('Illuminate\Auth\Access\AuthorizationException');

        $admin = create(Admin::class, ['role' => 'unauthorized']);

        $this->signIn($admin);

        $this->delete(route('admin.pianists.destroy', $this->pianist->slug));
    }
}
