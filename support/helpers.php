<?php

function tutorials()
{
	return [
		[
			'piece' => 'Andantino by Khachaturian',
			'title' => 'Working on double notes',
			'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam.'],
	];
}

function datatable($data)
{
	return new \App\Resources\DataTables\Builder($data);
}

function bugreport($e)
{
	return \Bugsnag::notifyException($e);
}

function minutes($count)
{
	return 60 * $count;
}

function hours($count)
{
	return minutes(60) * $count;
}

function days($count)
{
	return hours(24) * $count;
}

function weeks($count)
{
	return days(7) * $count;
}

function possessive($str)
{
	if (str_ends_with($str, ['s']))
		return $str.'\'';

	return $str.'\'s';
}

function getMonthName($number)
{
	return date("F", mktime(0, 0, 0, $number, 1));
}

function gender($name)
{
	try {
		return \Genderize::name($name)->get()->result[0]->gender;
	} catch (\Exception $e) {
		return null;
	}
}

function array_infinite($array, $key)
{
	return $array[intval(fmod($key,count($array)))];
}

function rm_whitespaces($string)
{
	return preg_replace('/\s+/', ' ',$string);
}

function lastchar($string)
{
	return substr($string, -1);
}

function youtube($input)
{
	return "https://www.youtube.com/results?search_query={$input}";
}

function calculateReadingTime($text)
{
    // 124 is the number of words we read per minute on average
    return intval(ceil(str_word_count(strip_tags($text)) / 124));
}

function avg(array $numbers)
{
	return intval(round(array_sum($numbers) / count($numbers)));
}

function production()
{
	return app()->environment() == 'production';
}

function local()
{
	return app()->environment() == 'local';
}

function testing()
{
	return app()->environment() == 'testing';
}

function hex($symbol, $type = 'html')
{
	return (new \App\Tools\Hex)->get($symbol, $type);
}

function class_str($class, $plural = false)
{
	$str = ucwords(camel_str(str_replace('\\', '', substr($class, strrpos($class, '\\')))));
	
	if ($plural)
		return str_plural($str, 2);

	return $str; 
}

function preview($text, $length)
{
    $text = strip_tags($text);
    preg_match("/(?:\w+(?:\W+|$)){0,$length}/", $text, $matches);

    return rtrim($matches[0], ' ') . '...';
}

function camel_str($camel) {
	preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $camel, $matches);
	$ret = $matches[0];
	foreach ($ret as &$match) {
		$match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
	}
	return implode(' ', $ret);
}

function emoji($type, $number)
{
	$emojis = [
		'happy' => ['😃', '🤗'],
		'birthday' => ['🎉', '👏', '🤗', '🎁', '🎂', '🍾', '😃', '🥳']
	];

	if (! array_key_exists($type, $emojis))
		return null;

	$array = $emojis[$type];
	shuffle($array);

	return array_slice($array, 0, $number);
}

function wiki($str)
{
	return 'https://wikipedia.com/wiki/' . $str;
}

function shuffle_assoc($array)
{
	$shuffled_array = array();

	$keys = array_keys($array);
	
	shuffle($keys);

	foreach ($keys as $key)
	{
		$shuffled_array[$key] = $array[$key];
	}

	return $shuffled_array;
}

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

function noteToMachine($note) {
	$result = str_replace('+', '#', $note);
	$result = str_replace('-', 'b', $result);
	$result = str_replace('##', 'x', $result);

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

function valid_email($email)
{
	return strhas($email, '@') && strhas($email, '.');
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

function arrayToSentence($array, $conjunction = 'and')
{
	$arrayCount = count($array);

	if ($arrayCount == 1) {
	    $sentence = $array[0];
	} else {
	    $partial = array_slice($array, 0, $arrayCount-1);
	    $sentence = implode(', ', $partial) . " $conjunction " . $array[$arrayCount-1];
	}

	return $sentence;
}

function slug_str($slug)
{
    return ucwords(str_replace('-', ' ', $slug));
}

function snake_str($snake, $ucfirst = false)
{
	if ($ucfirst)
		return ucfirst(str_replace('_', ' ', $snake));
    
    return ucwords(str_replace('_', ' ', $snake));
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
	return \Carbon\Carbon::parse($string)->setTimezone(config('app.timezone'));
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
		'lightpink' => ['#0078DEFF', '#4FAAF0FF'],
		'lightblue' => ['#0078DEFF', '#4FAAF0FF'],
		'darkblue' => ['#0048BBFF', '#2F77DDFF'],
		'darkpink' => ['#F761A1', '#8C1BAB'],
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
	$catalogues = ['Op.', 'B', 'KV', 'K', 'H', 'D', 'Hob', 'BWV', 'WoO', 'Op. posth.', 'Anh', 'Sz', 'S', 'L'];
	
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

function str_ends_with($str, $chars)
{
	return in_array(substr($str, -1), $chars);
}

function str_rm($str, $remove)
{
	return str_replace($remove, '', $str);
}

function testimonials()
{
	return  [
        [
            'title' => 'Amazing idea',
            'content' => 'This app helped me find so many great pieces I had never heard of, keep it up guys!',
            'author' => 'William Olson'
        ],
        [
            'title' => 'Looking forward to it!',
            'content' => 'I tested the app and saw a lot of potential it in, great content for beginner pianists like me, looking forward to the release.',
            'author' => 'Andrea Petrică'
        ],
        [
            'title' => 'Super sick!',
            'content' => 'If you have an Apple device you should really try it out. Thanks for the awesome work, keep going!',
            'author' => 'Anonymous'
        ],
        [
            'title' => 'Nice app',
            'content' => 'I\'ve been looking for something like this for a while, it has helped me a lot! Love the playlists:)',
            'author' => 'Patricia Palmer'
        ],
        [
            'title' => 'Very cool',
            'content' => 'Love this app!!!',
            'author' => 'Barbara Estep'
        ],
        [
            'title' => 'Great app!',
            'content' => 'I am a teacher and this app helps me find pieces to my students, highly recommend this!',
            'author' => 'Anonymous'
        ],
        [
            'title' => 'Very helpful',
            'content' => 'This app has been super helpful to guide my progress and show me pieces at my level that actually sound nice, keep it up!!',
            'author' => 'Marcos Madeira'
        ],
    ];
}