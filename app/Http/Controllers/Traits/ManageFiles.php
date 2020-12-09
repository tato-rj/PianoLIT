<?php

namespace App\Http\Controllers\Traits;

trait ManageFiles
{
	protected $input, $path;

	public function upload($name, $folder, $disk = 'public')
	{
		if (! $this->input)
			return $this->path;

		$filename = 'pianolit-'.str_slug(request()->$name).'-'.lastnchar(mt_rand(), 4).'.'.request()->file($this->input)
					   ->extension();

		return request()->file($this->input)
					   ->storeAs('app/' . $folder, $filename, $disk);
	}

	public function hasFile($input)
	{
		$this->path = null;
		
		$this->input = request()->hasFile($input) ? $input : null;

		return $this;
	}

	public function delete($path)
	{
		if ($this->input && \Storage::disk('public')->exists($path)) {
			\Storage::disk('public')->delete($path);
		} else {
			$this->path = $path;
		}

		return $this;
	}
}