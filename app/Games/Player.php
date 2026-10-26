<?php

namespace App\Games;

use Illuminate\Database\Eloquent\Model;

use Carbon\CarbonInterval;

class Player extends Model
{
    protected $table = 'game_scores';
    protected $guarded = [];
    protected $casts = ['score' => 'integer', 'rounds' => 'integer', 'duration' => 'integer', 'accuracy' => 'float', 'finalScore' => 'integer'];

    public function scopeByGame($query, $name)
    {
        return $query->where('game', $name);
    }

    public function scopeRange($query, $range = null)
    {
        switch ($range) {
            case 'week':
                return $query->whereBetween('created_at', [
                    now()->subDays(7),
                    now()
                ]);
            default:
                return $query;
        }
    }

    public function calculateFinalScore($results = null)
    {
        $model = $results ?? $this;

        $accuracyFactor = pow($model->accuracy / 1000, 1.2);
        $timeFactor = pow(55 / ($model->duration + 35), 0.25);

        $result =
            (
                pow($model->score, 1.06) *
                $accuracyFactor *
                pow($model->rounds + 1, 0.32) *
                $timeFactor
            ) * 85;

        return $results
            ? (int) round($result)
            : $this->update(['finalScore' => (int) round($result)]);
    }

    public function getAccuracyForHumansAttribute()
    {
        return rtrim(rtrim(number_format((float) $this->accuracy, 2), '0'), '.') . '%';
    }

    public function getDurationForHumansAttribute()
    {
        return CarbonInterval::seconds($this->duration)->cascade()->format('%I:%S');
    }
}
