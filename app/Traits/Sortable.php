<?php

namespace App\Traits;

trait Sortable
{
	public function scopeSorted($query)
	{
		return $query->orderBy('order');
	}

	public function scopeSort($query, $ids)
	{
        foreach ($ids as $order => $id) {
            $this->find($id)->update(['order' => $order]);
        }
	}
}
