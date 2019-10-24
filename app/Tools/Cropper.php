<?php

namespace App\Tools;

use Illuminate\Http\Request;

class Cropper
{
	protected $request, $file, $image, $path, $filename, $thumbnail, $crop;

	public function __construct(Request $request, bool $crop)
	{
		$this->request = $request;
		$this->crop = $crop;
	}

	public function make($name)
	{
		$this->file = $this->request->file($name);

        $this->image = \Image::make($this->file);

        if ($this->crop) {
        	$this->image = $this->image->crop(
	            intval($this->request->cropped_width),
	            intval($this->request->cropped_height), 
	            intval($this->request->cropped_x), 
	            intval($this->request->cropped_y)
	        );
        }

        if ($this->thumbnail) {
	        $this->thumbnail = \Image::make($this->file);

	        if ($this->crop) {
	        	$this->thumbnail = $this->thumbnail->crop(
		            intval($this->request->cropped_width),
		            intval($this->request->cropped_height), 
		            intval($this->request->cropped_x), 
		            intval($this->request->cropped_y)
		        );
	        }

	        $this->thumbnail = $this->thumbnail->resize(400, null, function ($constraint) {
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
