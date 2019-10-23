<?php

namespace App\Tools;

use Illuminate\Http\Request;

class Cropper
{
	protected $request, $file, $image, $path, $filename, $thumbnail;

	public function __construct(Request $request)
	{
		$this->request = $request;
	}

	public function make($name)
	{
		$this->file = $this->request->file($name);
dd($this->file);
        $this->image = \Image::make($this->file)->crop(
            intval($this->request->cropped_width),
            intval($this->request->cropped_height), 
            intval($this->request->cropped_x), 
            intval($this->request->cropped_y)
        );

        if ($this->thumbnail) {
	        $this->thumbnail = \Image::make($this->file)->crop(
	            intval($this->request->cropped_width),
	            intval($this->request->cropped_height), 
	            intval($this->request->cropped_x), 
	            intval($this->request->cropped_y)
	        )->resize(400, null, function ($constraint) {
	    		$constraint->aspectRatio();
	    	});
	    }

        return $this;
	}

	public function getPath()
	{
		return $this->path;
	}

	public function withThumbnail()
	{
		$this->thumbnail = true;

		return $this;
	}

	public function saveTo($folder, $withThumbnail = false)
	{
		$this->path = $folder . $this->file->getClientOriginalName();

		\Storage::disk('public')->put($this->path, (string) $this->image->encode());
        
        if ($this->thumbnail) {
			$path = $folder . 'thumbnails/' . $this->file->getClientOriginalName();

			\Storage::disk('public')->put($path, (string) $this->thumbnail->encode());
        }

		return $this;
	}
}
