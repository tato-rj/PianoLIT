<?php

namespace App\Tools;

use Illuminate\Http\Request;

class Cropper
{
	protected $request, $file, $image, $path, $filename;

	public function __construct(Request $request)
	{
		$this->request = $request;
	}

	public function make($name)
	{
		$this->file = $this->request->file($name);

        $this->image = \Image::make($this->file)->crop(
            intval($this->request->cropped_width),
            intval($this->request->cropped_height), 
            intval($this->request->cropped_x), 
            intval($this->request->cropped_y)
        );

        return $this;
	}

	public function getPath()
	{
		return $this->path;
	}

	public function saveTo($folder)
	{
		$this->path = $folder . $this->file->getClientOriginalName();

		\Storage::disk('public')->put($this->path, (string) $this->image->encode());

		return $this;
	}
}
