<?php

namespace Tests\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Cloudinary\CloudinarySandbox;

trait InteractsWithCloudinary
{
	public function uploadedVideo($filename = 'video.mp4', $size = 2000)
	{
		Storage::fake('public');

		return UploadedFile::fake()->create($filename)->size($size);
	}

    public function fakeCloudinaryWebhook()
    {
        $notification = (new CloudinarySandbox)->notificationFor(auth()->user()->performances()->processing()->first());

        $this->post(route('webhooks.cloudinary', $notification));
    }
}