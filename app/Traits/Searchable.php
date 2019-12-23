<?php

namespace App\Traits;

trait Searchable
{
    public function scopeSearch($query, $array, $request = null)
    {
        if (empty($array))
            return $query->take(0);

        $results = $query->where(function($query) use ($array, $request) {

            foreach ($array as $tag) {
                $suffix = is_numeric(lastchar($tag)) ? null : '%';
                $query->whereHas('tags', function($q) use ($tag, $suffix) {
                       $q->where('name', 'like', "%$tag$suffix"); 
                });

                if (is_null($request) || $request->has('global')) {
                    $query->orWhereHas('composer', function($q) use ($tag) {
                           $q->where('name', 'like', "%$tag%")
                             ->orWhere('gender', 'like', "%$tag%");
                    })->orWhere('nickname', 'like', "%$tag%")
                      ->orWhere('name', 'like', "%$tag%")
                      ->orWhere('collection_name', 'like', "%$tag%")
                      ->orWhere('catalogue_name', $tag)
                      ->orWhere('catalogue_number', $tag);
                }
            }
            
        });

        return $results;
    }
}
