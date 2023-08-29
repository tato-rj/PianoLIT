<?php

namespace App\Cloudinary;

use Illuminate\Http\UploadedFile;
use App\Performance;

class CloudinaryApi
{
	protected $quality = 60;
	protected $namespace = 'user-videos';

	public function __construct()
	{
		$this->folder = $this->namespace . '/' . auth()->user()->email;
	}

	public function upload(UploadedFile $file)
	{
        $options = [
            'public_id' => \Str::uuid()->toString(),
            'folder' => $this->folder,
            // 'notification_url' => 'endpoint',
            'async' => true,
            'transformation' => [
                'quality' => $this->quality,
                'effect'=> 'fade:2000'
            ]
        ];

        if (testing()) {
        	$this->response = (new CloudinarySandbox)->uploadVideo($file->getRealPath(), $options);
        } else {
        	$this->response = cloudinary()->uploadVideo($file->getRealPath(), $options);
        }

        return $this;
	}

	public function find(Performance $performance)
	{
		$record = cloudinary()->search()->expression('public_id:'.$performance->public_id)->execute()['resources'];

		if ($record)
			return $record[0];
	}

	public function delete(Performance $performance)
	{
		if (testing()) {
			return ['result' => 'ok'];
		} else {
			return cloudinary()->destroy($performance->public_id, ['resource_type' => 'video']);
		}
	}

	public function publicId()
	{
		return $this->response->getPublicId();
	}

	public function getThumbnailFrom($url)
	{
		$ext = pathinfo($url, PATHINFO_EXTENSION);

		return str_replace($ext, 'jpg', $url);
	}
}