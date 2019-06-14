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
        $levelsStats = Tag::levels()->withCount('pieces')->get();

        foreach ($levelsStats as $stat) {
            if (percentage($stat->pieces_count, $this->pieces_count) < 15) {
                array_push($array, $stat->name);
            }
        }

        return $array;
    }

    public function periods()
    {
    	$array = [];
        $periodsStats = Tag::periods()->withCount('pieces')->get();

        foreach ($periodsStats as $stat) {
            if (percentage($stat->pieces_count, $this->pieces_count) < 10) {
                array_push($array, $stat->name);
            }
        }

        return $array;
    }
}
