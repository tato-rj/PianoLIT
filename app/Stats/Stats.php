<?php

namespace App\Stats;

use App\User;

class Stats {
	protected $table, $data, $title, $colors;
	protected $color = [
	    'purple' => '#9561e2', 
	    'red' => '#e3342f', 
	    'orange' => '#f6993f', 
	    'green' => '#38c172', 
	    'cyan' => '#4dc0b5', 
	    'blue' => '#3490dc', 
	    'pink' => '#f66d9b',
	    'grey' => '#cecccc'
	];

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
		return [
			'title' => $this->title, 
			'labels' => $this->data->pluck('label'), 
			'records' => $this->data->pluck('count'),
			'colors' => $this->colors
		];
	}

	public function origin($origin)
	{
		if ($origin)
			$this->table = $this->table->where('origin', $origin);

		return $this;
	}

	public function daily()
	{
		$this->title = 'New sign ups';
		$this->colors = [$this->color['blue']];
		$this->data = $this->table->selectRaw('DATE_FORMAT(created_at, "%M %D") as label, count(*) as count')
                    ->groupBy('label')
                    ->orderByRaw('min(created_at)')
                    ->get();
        return $this;
	}

	public function monthly()
	{
		$this->title = 'New sign ups';
		$this->colors = [$this->color['green']];
        $this->data = $this->table->selectRaw('DATE_FORMAT(created_at, "%M") as label, count(*) as count')
                    ->groupBy('label')
                    ->orderByRaw('min(created_at)')
                    ->get();
        return $this;
	}

	public function yearly()
	{
		$this->title = 'New sign ups';
		$this->colors = [$this->color['orange']];
        $this->data = $this->table->selectRaw('DATE_FORMAT(created_at, "%Y") as label, count(*) as count')
                    ->groupBy('label')
                    ->orderByRaw('min(created_at)')
                    ->get();
        return $this;
	}

	public function gender()
	{
		$this->title = 'Users by gender';
		$this->colors = [$this->color['pink'], $this->color['blue']];
        $this->data = $this->table->whereNotNull('gender')->selectRaw('gender as label, count(*) count')
                    ->groupBy('label')
                    ->orderBy('label')
                    ->get();
        return $this;
	}

	public function confirmed()
	{
		$this->title = 'Email confirmed';
		$this->colors = [$this->color['cyan'], $this->color['grey']];
		$total = $this->table->count();
		$verified = $this->table->whereNotNull('email_verified_at')->count();

        $this->data = collect([
			[
				'label' => 'verified',
				'count' => $verified
			],
			[
				'label' => 'not verified',
				'count' => $total - $verified
			]
        ]);

        return $this;
	}

	public function favorites()
	{
		$this->title = 'Favorites';
		$this->colors = [$this->color['red'], $this->color['grey']];
		$total = User::where('origin', 'ios')->count();
		$hasFavs = User::where('origin', 'ios')->has('favorites')->count();

        $this->data = collect([
			[
				'label' => 'with favorites',
				'count' => $hasFavs
			],
			[
				'label' => 'no favorites',
				'count' => $total - $hasFavs
			]
        ]);

        return $this;
	}
}
