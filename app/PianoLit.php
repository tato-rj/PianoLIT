<?php

namespace App;

use App\Traits\{BelongsToThrough, Reportable, ManageDates};
use Illuminate\Database\Eloquent\Model;

class PianoLit extends Model
{
	use BelongsToThrough, Reportable, ManageDates;
	
	protected $guarded = [];

    public function scopeNewest($query)
    {
        return $query->orderBy('created_at');
    }

    public function scopeAlphabetical($query, $column)
    {
        return $query->orderBy($column);
    }

    public function scopeLastUpdated($query)
    {
        return $query->orderBy('updated_at','DESC');
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
