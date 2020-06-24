<?php

namespace App\Files;

use Illuminate\Http\Request;

abstract class FileUpload
{
	protected $folder, $model, $request, $input, $name, $attribute, $withThumbnail;

	public function __construct(Request $request)
	{
		$this->request = $request;
	}

	public function take($input)
	{
		$this->input = $input;
		$this->attribute = str_replace($this->suffix, 'path', $input);

		return $this;
	}

	public function withThumbnail()
	{
		$this->withThumbnail = true;

		return $this;
	}

	public function name($name)
	{
		$this->name = $name;

		return $this;
	}

	public function generateFilename()
	{
		$extension = $this->request->file($this->input)->extension();

		return 'pianolit-' . $this->name . '-' . lastnchar(mt_rand(), 4) . '.' . $extension;
	}

	public function for($class, $customAttribute = null)
	{
		if ($class instanceof \Illuminate\Database\Eloquent\Model)
			$this->model = $class;

		if ($customAttribute)
			$this->attribute = $customAttribute;

		$this->folder = strtolower(class_basename($class));

		return $this;	
	}

	public function upload()
	{
		if (! $this->request->hasFile($this->input)) {
			$attribute = $this->attribute;

			return $this->model ? $this->model->$attribute : null;
		}

		return $this->model ? $this->update() : $this->save();
	}

	public function update()
	{
		$this->delete();
		
		return $this->save();
	}

	public function delete()
	{
		$attribute = $this->attribute;

		if ($this->withThumbnail)
			\Storage::disk('public')->delete($this->model->thumbnail_path);

		\Storage::disk('public')->delete($this->model->$attribute);
	}
}
