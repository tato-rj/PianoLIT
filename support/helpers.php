<?php

function storage($path)
{
	if ($path)
		return asset(\Storage::url($path));

	return null;
}

function dateToDatabase($date)
{
	return \Carbon\Carbon::parse($date)->format('Y-m-d');
}

function carbon($string)
{
	return \Carbon\Carbon::parse($string);
}

function validate($errors, $input)
{
    return $errors->has($input) ? 'is-invalid' : null;
}

function randval($array)
{
	return $array[rand(0, count($array) - 1)];
}

function splitname($name)
{
	$array = explode(' ', $name);
	
	return ['first' => current($array), 'last' => end($array)];
}

function removeAccents($string) {
    return strtolower(trim(preg_replace('~[^0-9a-z]+~i', ' ', preg_replace('~&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '$1', htmlentities($string, ENT_QUOTES, 'UTF-8'))), ' '));
}

function keys()
{
	return ['C major', 'C minor', 'C# major', 'C# minor', 'Db major', 'Db minor', 'D major', 'D minor', 'D# major', 'D# minor', 'Eb major', 'Eb minor', 'E major', 'E minor', 'F major', 'F minor', 'F# major', 'F# minor', 'Gb major', 'Gb minor', 'G major', 'G minor', 'G# major', 'G# minor', 'Ab major', 'Ab minor', 'A major', 'A minor', 'A# major', 'A# minor', 'Bb major', 'Bb minor', 'B major', 'B minor'];
}

function gradient($color)
{
	$colors = [
		'red' => ['#C40025FF', '#F72545FF'],
		'orange' => ['#DA2D00FF', '#F0683BFF'],
		'yellow' => ['#E68300FF', '#EFB600FF'],
		'pink' => ['#D0277EFF', '#F167ABFF'],
		'lightblue' => ['#0078DEFF', '#4FAAF0FF'],
		'darkblue' => ['#0048BBFF', '#2F77DDFF'],
		'green' => ['#00A490FF', '#44D0B2FF'],
		'teal' => ['#0098C3FF', '#34CAD2FF'],
		'purple' => ['#4C22B9FF', '#7952E5FF'],
		'blue' => ['#2960F7FF', '#008CF5FF']
	];

	if (array_key_exists($color, $colors))
		return $colors[$color];

	return ['#fff', '#000'];
}

function catalogues()
{
	$catalogues = ['Op.', 'KV', 'H', 'D', 'Hob', 'BWV', 'WoO', 'Op. posth.', 'Anh', 'Sz', 'S', 'L'];
	
	sort($catalogues);

	return $catalogues;
}

function periods()
{
	return ['baroque', 'classical', 'romantic', 'impressionist', 'modern'];
}

function nationalities()
{
	return ['german', 'czech', 'american', 'italian', 'spanish', 'french', 'russian', 'austrian'];
}

function lengths()
{
	return ['short', 'medium', 'long'];
}

function levels()
{
	return ['beginner', 'intermediate', 'advanced'];
}

function upload($file, $folder)
{
    if ($file) {
        return \Illuminate\Support\Facades\Storage::disk('local')->put("public/$folder", $file);
    }
}

function remove($paths)
{
	foreach ($paths as $path) {
		\Illuminate\Support\Facades\Storage::disk('local')->delete($path);
	}

	return true;
}

function lookup($file)
{
	return $file ? 'text-success' : 'text-danger';
}
