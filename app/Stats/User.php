<?php

namespace App\Stats;

class User
{
	public function __construct($user)
	{
		$this->user = $user;
        $this->total = $user->count();
	}

	public function daily()
	{
        $results = $this->user::selectRaw('day(created_at) day, monthname(created_at) month, year(created_at) year, count(*) count')
                    ->groupBy('year', 'month', 'day')
                    ->orderByRaw('min(created_at)')
                    ->get();

        return $results;	
	}

	public function monthly()
	{
        $results = $this->user::selectRaw('monthname(created_at) month, year(created_at) year, count(*) count')
                    ->groupBy('year', 'month')
                    ->orderByRaw('min(created_at)')
                    ->get();

        return $results;	
	}

	public function yearly()
	{
        $results = $this->user::selectRaw('year(created_at) year, count(*) count')
                    ->groupBy('year')
                    ->orderByRaw('min(created_at)')
                    ->get();

        return $results;	
	}

    public function age()
    {
        return $this->user->selectRaw('age_range as age, count(*) as count')->groupBy('age_range')->get();
    }

    public function occupation()
    {
        return $this->user->selectRaw('occupation, count(*) as count')->groupBy('occupation')->get();
    }

    public function experience()
    {
        return $this->user->selectRaw('experience, count(*) as count')->groupBy('experience')->get();
    }
}
