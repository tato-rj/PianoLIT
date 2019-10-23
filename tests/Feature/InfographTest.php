<?php

namespace Tests\Feature;

use Tests\AppTest;
use App\Infograph;
use Illuminate\Http\UploadedFile;
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

        $quiz = $this->storeInfograph();

        $this->assertNotEquals($quiz->thumbnail_path, $quiz->cover_path);
        
        $this->assertTrue(Storage::disk('public')->exists($quiz->cover_path));

        $this->assertTrue(Storage::disk('public')->exists($quiz->thumbnail_path));
    }
}
