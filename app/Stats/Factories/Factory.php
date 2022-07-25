<?php

namespace App\Stats\Factories;

abstract class Factory
{
	protected $color = [
	    'purple' => '#9561e2', 
	    'red' => '#e3342f', 
	    'orange' => '#f6993f', 
	    'green' => '#38c172', 
	    'cyan' => '#4dc0b5', 
	    'blue' => '#3490dc', 
	    'pink' => '#f66d9b',
	    'grey' => '#cecccc',
        'yellow' => '#f6e978'
	];
    
    public function get()
    {
        return [
            'labels' => $this->data->pluck('label'),
            'datasets' => $this->getDatasets()
        ];
    }

	public function getDatasets()
	{
		$records = [];

        if ($this->data->first() && collect($this->data->first())->has('datasets')) {
            foreach ($this->data as $data) {
                foreach ($data['datasets'] as $name => $count) {
                    $records[$name]['title'] = $name;

                    if (! array_key_exists('records', $records[$name]))
                        $records[$name]['records'] = [];
                    
                    array_push($records[$name]['records'], $count);
                    $records[$name]['colors'] = $this->colors;
                }
            }
        } else {
            array_push($records, [ 
                'title' => $this->title,
                'records' => $this->data->pluck('count'),
                'colors' => $this->colors
            ]);
        }

        return array_values($records);
	}

    public function getColor($color)
    {
        return $this->color[$color];
    }

    public function where($conditions = [])
    {
        foreach ($conditions as $key => $condition) {
            if ($key && $condition)
                $this->table = $this->table->where($key, $condition);
        }

        return $this;
    }
}
