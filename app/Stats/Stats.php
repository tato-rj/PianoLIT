<?php

namespace App\Stats;

class Stats {
	protected $table, $data;

	public function for($table)
	{
		$this->table = \DB::table($table);

		return $this;
	}

	public function query(string $method, $args = null)
	{
		if (method_exists($this, $method))
			return $this->$method($args);

		throw new \Exception('The method ' . $method . ' does not exist.', 404);
	}

	public function get()
	{
		return ['labels' => $this->data->pluck('label'), 'records' => $this->data->pluck('count')];
	}

	public function daily()
	{
		$this->data = $this->table->selectRaw('DATE_FORMAT(created_at, "%M %D") label, count(*) count')
                    ->groupBy('label')
                    ->orderByRaw('min(created_at)')
                    ->get();
        return $this;
	}

	public function monthly()
	{
        $this->data = $this->table->selectRaw('DATE_FORMAT(created_at, "%M") label, count(*) count')
                    ->groupBy('label')
                    ->orderByRaw('min(created_at)')
                    ->get();
        return $this;
	}

	public function yearly()
	{
        $this->data = $this->table->selectRaw('DATE_FORMAT(created_at, "%Y") label, count(*) count')
                    ->groupBy('label')
                    ->orderByRaw('min(created_at)')
                    ->get();
        return $this;
	}

	public function gender()
	{
        $this->data = $this->table->selectRaw('gender, count(*) count')
                    ->groupBy('gender')
                    ->get();
        return $this;
	}
}
