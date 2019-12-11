<?php

namespace App\Tools;

use App\Piece;

class Stats
{
  protected $model;
  protected $milestones = [100, 500, 1000, 2000, 5000, 10000];

  public function model($model)
  {
    $this->model = new $model;

    return $this;
  }

  public function progress($count)
  {
    return \DB::table($this->model->getTable())
    ->selectRaw('month(created_at) month, day(created_at) day, year(created_at) year, count(*) count')
    ->groupBy('month', 'day', 'year')
    ->orderByRaw('min(created_at)')
    ->get()
    ->slice(-$count)
    ->values();
  }

  public function average($days)
  {
    $count = $this->model->whereBetween('created_at', [now()->copy()->subDays($days), now()])->count();

    $avg = $count / $days;

    return $avg < 1 ? null : (int)round($avg);
  }

  public function milestone($avg)
  {
    $count = $this->model->count();
    $report = [];

    foreach ($this->milestones as $milestone) {
      if ($count < $milestone) {
        $report['current'] = $count;
        $report['goal'] = $milestone;

        if (is_integer($avg) && $avg > 0)
          $report['days_left'] = (int)round(($milestone - $count) / $avg);

        break;
      }
    }

    return $report;
  }
}
