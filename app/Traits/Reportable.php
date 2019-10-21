<?php

namespace App\Traits;

trait Reportable
{
	public function getReportNameAttribute()
	{
		if (! $this->report_by)
			return null;

		$name = $this->report_by;

		return $this->$name;
	}

	public function scopeReport($query, $days, $initialDate)
	{
		return $query->whereBetween('created_at', [now()->subDays($days), $initialDate]);
	}
}
