<?php

namespace App\Tools;

use App\{Piece, Tag, Composer};

class Alerts
{
	protected $pieces_count;

	public function __construct()
	{
		$this->pieces_count = Piece::count();
	}

    public function generate($alerts)
    {
        $array = [];

        foreach ($alerts as $alert) {
        	if (method_exists($this, $alert))
        		$array = array_merge($array, $this->$alert());
        }

        $sentence = $this->toSentence($array);

        return $sentence;
    }

    public function toSentence($array)
    {
        $arrayCount = count($array);

        if ($arrayCount == 0)
            return null;

        if ($arrayCount == 1) {
            $sentence = $array[0];
        } else {
            $partial = array_slice($array, 0, $arrayCount-1);
            $sentence = implode(', ', $partial) . ' and ' . $array[$arrayCount-1];
        }

        return $sentence;	
    }

    public function levels()
    {
    	$array = [];
        $levels = Tag::levels()->withCount('pieces')->get();

        foreach ($levels as $level) {
            if (percentage($level->pieces_count, $this->pieces_count) < 15) {
                array_push($array, $level->name);
            }
        }

        return $array;
    }

    public function periods()
    {
    	$array = [];
        $periods = Tag::periods()->withCount('pieces')->get();

        foreach ($periods as $period) {
            if (percentage($period->pieces_count, $this->pieces_count) < 10) {
                array_push($array, $period->name);
            }
        }

        return $array;
    }

    public function composers()
    {
    	$array = [];
        $composers = Composer::famous()->withCount('pieces')->get();

        foreach ($composers as $composer) {
            if (percentage($composer->pieces_count, $this->pieces_count) < 4) {
                array_push($array, $composer->name);
            }
        }

        return $array;    	
    }
}
