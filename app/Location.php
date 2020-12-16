<?php

namespace App;

class Location extends PianoLit
{
    public function scopeCreateOrUpdate($query, $userId, $data)
    {
    	if ($record = $this->find($userId)) {
    		$record->update($data);
    	} else {
    		$this->create($data);
    	}

    	return $this->find($userId);
    }
}
