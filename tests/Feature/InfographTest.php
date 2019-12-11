<?php

namespace Tests\Feature;

use Tests\AppTest;
use App\Infograph\{Infograph, Topic};
use Tests\Traits\AdminEvents;

class InfographTest extends AppTest
{
    use AdminEvents;

    /** @test */
    public function an_admin_can_upload_a_new_infograph()
    {
        $this->signIn();

        $request = $this->storeInfograph();

        $this->assertDatabaseHas('infographs', ['name' => $request->name]);
    }

    /** @test */
    public function a_thumbnail_is_automatically_generated_when_an_infograph_is_created()
    {
        $this->signIn();

        $infograph = $this->storeInfograph();

        $this->assertNotEquals($infograph->thumbnail_path, $infograph->cover_path);
        
        $this->assertTrue(\Storage::disk('public')->exists($infograph->cover_path));

        $this->assertTrue(\Storage::disk('public')->exists($infograph->thumbnail_path));
    }

    /** @test */
    public function an_admin_can_publish_an_infograph()
    {
        $this->signIn();

        $infograph = create(Infograph::class, ['published_at' => null]);

        $this->assertNull($infograph->published_at);

        $this->patch(route('admin.infographs.update-status', $infograph->slug));

        $this->assertNotNull($infograph->fresh()->published_at);
    }

    /** @test */
    public function an_admin_can_update_an_infograph()
    {
        $this->signIn();

        $infograph = $this->storeInfograph();

        $name = $infograph->name;
        
        $update = make(Infograph::class, ['name' => 'New name']);
        
        $this->patch(route('admin.infographs.update', $infograph->slug), [
            'name' => $update->name,
            'description' => $update->description,
            'topics' => $update->topics
        ]);

        $this->assertNotEquals($name, $infograph->fresh()->name);       
    }

    /** @test */
    public function an_admin_can_remove_an_infograph()
    {
        $this->signIn();

        $infograph = $this->storeInfograph();

        $this->delete(route('admin.infographs.destroy', $infograph->slug));

        $this->assertDatabaseMissing('quizzes', ['title' => $infograph->title]);

        \Storage::disk('public')->assertMissing($infograph->cover_path);
        \Storage::disk('public')->assertMissing($infograph->thumbnail_path);
    }

    /** @test */
    public function an_infograph_automatically_increments_its_download_count_each_time_it_is_downloaded()
    {
        $this->signIn();

        $infograph = $this->storeInfograph();

        $this->logout();
        
        $this->signIn($this->user);

        $downloads = $infograph->downloads;

        $this->get(route('infographs.download', $infograph->slug));

        $this->assertNotEquals($downloads, $infograph->fresh()->downloads);
    }

    /** @test */
    public function a_visitor_can_give_thumbs_up_or_down_to_an_infograph()
    {
        $infograph = create(Infograph::class);

        $this->assertEquals(0, $infograph->score);
        
        $this->post(route('infographs.update-score', $infograph->slug), ['liked' => true]);

        $this->assertEquals(1, $infograph->fresh()->score);
        
        $this->post(route('infographs.update-score', $infograph->slug), ['liked' => false]);

        $this->assertEquals(0, $infograph->fresh()->score);
    }
}
