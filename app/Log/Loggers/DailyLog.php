<?php

namespace App\Log\Loggers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

class DailyLog
{
	protected $date;

	public function __construct()
	{
		$this->prefix = config('database.redis.prefix');
		$this->namespace = 'logs:daily:';
	}

	public function all($date = null)
	{
		$from = $date ? $date->format('Y-m-d') : null;

        $logs = Redis::keys($this->prefix . $this->namespace . $from . '*');

        $records = collect();

        foreach ($logs as $log) {
            $records->push($this->keyToArray($log));
        }

        return $records->sortBy('date')->groupBy('date');
	}

	public function sum($record)
	{
        return $record->first()->sum(function($item) {
            $count = 0;

            if (array_key_exists('app', $item))
                $count += $item['app'];

            if (array_key_exists('web', $item))
                $count += $item['web'];

            if (array_key_exists('webapp', $item))
                $count += $item['webapp'];

            return $count;
        });
	}

	public function latest(int $days = 0)
	{
		$records = [];

		for ($i = $days; $i >= 0; $i--) {
			$date = now()->subDays($i);
			$log = $this->all(now()->subDays($i));

			$records[$i]['label'] = carbon($date)->format('D jS');
			$records[$i]['datasets']['app'] = 0;
			$records[$i]['datasets']['web'] = 0;
			$records[$i]['datasets']['webapp'] = 0;
			
			foreach (array_values($log->all()) as $events) {
				foreach ($events as $event) {
					if (array_key_exists('app', $event))
						$records[$i]['datasets']['app'] = $event['app'];

					if (array_key_exists('web', $event))
						$records[$i]['datasets']['web'] = $event['web'];

					if (array_key_exists('webapp', $event))
						$records[$i]['datasets']['webapp'] = $event['webapp'];
				}
			}
		}

		return collect(array_values($records));
	}

	public function between($min, $max)
	{
		$total = $this->all();

		dd($total);
	}

    public function getOrigin($log)
    {
        return substr($log, strrpos($log, ':') + 1);
    }

	public function getDate($timestamp)
	{
		$this->date = carbon($timestamp)->format('Y-m-d');

		return $this;	
	}

	public function increment($log)
	{
		Redis::incr($this->prefix . $this->namespace . $this->date . ':' . $this->getOrigin($log));
	}

	public function namespace()
	{
		return $this->namespace;
	}

	public function keyToArray($key)
	{
        $array = explode(':', $key);

        $date = $array[count($array)-2];

        $origin = $array[count($array)-1];

        return ['date' => $date, $origin => Redis::get($key)];
	}
}
