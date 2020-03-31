<?php

namespace App;

use App\Traits\{BelongsToThrough, Reportable};
use Illuminate\Database\Eloquent\Model;

class PianoLit extends Model
{
	use BelongsToThrough, Reportable;
	
	protected $guarded = [];

    public function scopeNewest($query)
    {
        return $query->orderBy('created_at');
    }

    public function scopeExclude($query, $ids)
    {
        return $query->whereNotIn('id', $ids);
    }

    public function scopeExcept($query, $column, $exclude)
    {
        return $query->whereNotIn($column, $exclude);
    }

    public function scopeExceptThis($query)
    {
        return $query->where('id', '!=', $this->id);
    }

    public function scopeOrderByMany($query, array $columns)
    {
        $result = $query;

        foreach ($columns as $column) {
            $result = $query->orderBy($column);
        }

        return $result;
    }
}
