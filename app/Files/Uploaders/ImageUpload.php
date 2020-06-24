<?php

namespace App\Files\Uploaders;

use App\Files\FileUpload;
use App\Tools\Cropper;

class ImageUpload extends FileUpload
{
	protected $cropped = false;
	protected $suffix = 'image';

	public function cropped()
	{
		$this->cropped = true;

		return $this;
	}

	public function save()
	{
		$cropper = (new Cropper($this->request, $this->cropped));

		if ($this->withThumbnail)
			$cropper->withThumbnail();

		return $cropper->make($this->input)->saveTo("$this->folder/$this->input/", $this->generateFilename());
	}
}
