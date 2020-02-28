<?php

namespace App\Stats;

abstract class StatsFactory
{
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

        if (array_key_exists('datasets', $this->data->first())) {
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
}
