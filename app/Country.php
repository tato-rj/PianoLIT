<?php

namespace App;

class Country extends PianoLit
{
    // Keep existing country/composer JSON contracts unchanged for mobile clients.
    protected $hidden = ['continent'];

    public function matchesLatinAmericaSearch(): bool
    {
        // Directory convention: all of the Americas except the US and Canada,
        // including the Caribbean regardless of the country's language.
        $continent = strtolower(trim((string) $this->continent));
        $code = strtolower(trim((string) $this->flag_code));
        $name = preg_replace('/[^a-z]/', '', strtolower((string) $this->name));

        return in_array($continent, ['north america', 'south america'], true)
            && ! in_array($code, ['us', 'ca'], true)
            && ! in_array($name, ['us', 'usa', 'unitedstates', 'unitedstatesofamerica', 'canada'], true);
    }

    public function composers()
    {
    	return $this->hasMany(Composer::class);
    }

    public function pieces()
    {
    	return $this->hasManyThrough(Piece::class, Composer::class);
    }
}
