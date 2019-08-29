<?php

function lastletter($word)
{
	return substr($word, -1);
}

function lastword($str)
{
	if (! $str || ! is_string($str))
		return null;

	$pieces = explode(' ', $str);

	return array_pop($pieces);
}

function noteToHumans($note) {
	$result = str_replace('+', '#', $note);
	$result = str_replace('-', 'b', $result);

	return ucfirst(str_replace('2', '', $result));
}

function iterationToHumans($key)
{
	$words = ['first', 'second', 'third', 'fourth', 'fifth', 'sixth', 'seventh', 'eigth', 'ninth', 'tenth', 'eleventh', 'twelveth'];

	if (array_key_exists($key, $words))
		return $words[$key];

	return null;
}

function chordToHumans($str)
{
	$chord = str_replace('2', '', $str);
	$chord = str_replace('+', '#', $chord);
	$chord = str_replace('-', 'b', $chord);

	return ucfirst(strip_tags($chord));
}

function sup($str)
{
	return '<sup class="extension">'. $str .'</sup>';
}

function enharmonic()
{
	return new \App\Resources\CircleOfFifths\Enharmonics;
}

function strhas($str, $needle)
{
	return strpos($str, $needle) !== false;
}

function array_has_array(array $haystack, array $needle) {
	$result = false;

	foreach ($haystack as $key => $array) {
		if (is_array($array) && $array == $needle) {
			$result = true;
			break;
		}
	}

  	return $result;
}

function next_letter($letter, $music = true)
{
	$nextLetter = strtolower($letter[0]);
	$nextLetter++;

	if ($music && $nextLetter == 'h')
		$nextLetter = 'a';

	return $nextLetter;
}

function storage($path)
{
	if ($path)
		return asset(\Storage::url($path));

	return null;
}

function arrayToSentence($array)
{
	$arrayCount = count($array);

	if ($arrayCount == 1) {
	    $sentence = $array[0] . '.';
	} else {
	    $partial = array_slice($array, 0, $arrayCount-1);
	    $sentence = implode(', ', $partial) . ' and ' . $array[$arrayCount-1];
	}

	return $sentence;
}

function traffic()
{
	return new \App\Tools\Traffic;
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
	$catalogues = ['Op.', 'KV', 'K', 'H', 'D', 'Hob', 'BWV', 'WoO', 'Op. posth.', 'Anh', 'Sz', 'S', 'L'];
	
	sort($catalogues);

	return $catalogues;
}

function periods()
{
	return ['baroque', 'classical', 'romantic', 'impressionist', 'modern', 'contemporary'];
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

function percentage($num1, $num2)
{
	return (int)round(($num1 * 100) / $num2);
}