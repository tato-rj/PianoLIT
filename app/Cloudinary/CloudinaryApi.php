<?php

namespace App\Cloudinary;

use Illuminate\Http\UploadedFile;

class CloudinaryApi
{
	protected $quality = 60;
	protected $namespace = 'user-videos';

	public function __construct()
	{
		$this->folder = $this->namespace . '\\' . auth()->user()->email;
	}

	public function upload(UploadedFile $file)
	{
        $options = [
            'public_id' => \Str::uuid()->toString(),
            'folder' => $this->folder,
            // 'notification_url' => 'endpoint',
            'async' => true,
            // 'transformation' => [
            //     'quality' => $this->quality,
            //     'effect'=> 'fade:2000'
            // ]
        ];

        if (testing()) {
        	$this->response = (new CloudinarySandbox)->uploadVideo($file->getRealPath(), $options);
        } else {
        	$this->response = cloudinary()->uploadVideo($file->getRealPath(), $options);
        }

        return $this;
	}

	public function publicId()
	{
		return $this->response->getPublicId();
	}
}