<?php

namespace Tests\Feature;

use App\Admin;
use Tests\AppTest;

class TopicTest extends AppTest
{
    /** @test */
    public function an_admin_can_create_topics()
    {
        $this->signIn();

        $this->post(route('admin.topics.store'), ['name' => 'new topic']);

        $this->assertDatabaseHas('topics', ['name' => 'new topic']);
    }

    /** @test */
    public function unauthorized_admins_cannot_create_topics()
    {
        $this->expectException('Illuminate\Auth\Access\AuthorizationException');

        $this->signIn(
            create(Admin::class, ['role' => 'unauthorized'])
        );

        $this->post(route('admin.topics.store'), ['name' => 'new topic']);
    }

    /** @test */
    public function admins_can_edit_the_topics()
    {
        $this->signIn();

        $this->patch(route('admin.topics.update', $this->topic->id), ['name' => 'new name']);

        $this->assertEquals($this->topic->fresh()->name, 'new name');
    }

    /** @test */
    public function unauthorized_admins_cannot_edit_topics()
    {
        $this->expectException('Illuminate\Auth\Access\AuthorizationException');

        $this->signIn(
            create(Admin::class, ['role' => 'unauthorized'])
        );

        $this->patch(route('admin.topics.update', $this->topic->id), ['name' => 'new topic']);
    }

    /** @test */
    public function admins_can_delete_topics()
    {
        $topicId = $this->topic->id;

        $this->signIn();

        $this->delete(route('admin.topics.destroy', $this->topic->id));

        $this->assertDatabaseMissing('topics', ['id' => $topicId]);
    }

    /** @test */
    public function unauthorized_admins_cannot_delete_topics()
    {
        $this->expectException('Illuminate\Auth\Access\AuthorizationException');

        $this->signIn(
            create(Admin::class, ['role' => 'unauthorized'])
        );

        $this->delete(route('admin.topics.destroy', $this->topic->id));
    }
}
