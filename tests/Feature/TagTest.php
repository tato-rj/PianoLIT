<?php

namespace Tests\Feature;

use App\Admin;
use Tests\AppTest;

class TagTest extends AppTest
{
    /** @test */
    public function an_admin_can_create_tags()
    {
        $this->signIn();

        $this->post(route('admin.tags.store'), ['name' => 'new tag', 'type' => 'type']);

        $this->assertDatabaseHas('tags', ['name' => 'new tag']);
    }

    /** @test */
    public function unauthorized_admins_cannot_create_tags()
    {
        $this->expectException('Illuminate\Auth\Access\AuthorizationException');

        $this->signIn(
            create(Admin::class, ['role' => 'unauthorized'])
        );

        $this->post(route('admin.tags.store'), ['name' => 'new tag']);
    }

    /** @test */
    public function admins_can_edit_the_tags()
    {
        $this->signIn();

        $this->patch(route('admin.tags.update', $this->tag->id), ['name' => 'new name']);

        $this->assertEquals($this->tag->fresh()->name, 'new name');
    }

    /** @test */
    public function unauthorized_admins_cannot_edit_tags()
    {
        $this->expectException('Illuminate\Auth\Access\AuthorizationException');

        $this->signIn(
            create(Admin::class, ['role' => 'unauthorized'])
        );

        $this->patch(route('admin.tags.update', $this->tag->id), ['name' => 'new tag']);
    }

    /** @test */
    public function admins_can_delete_tags()
    {
        $tagId = $this->tag->id;

        $this->signIn();

        $this->delete(route('admin.tags.destroy', $this->tag->id));

        $this->assertDatabaseMissing('tags', ['id' => $tagId]);
    }

    /** @test */
    public function unauthorized_admins_cannot_delete_tags()
    {
        $this->expectException('Illuminate\Auth\Access\AuthorizationException');

        $this->signIn(
            create(Admin::class, ['role' => 'unauthorized'])
        );

        $this->delete(route('admin.tags.destroy', $this->tag->id));
    }
}
