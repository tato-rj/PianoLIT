<?php

namespace App;

use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Model;

class Favorite extends PianoLit
{
    public function piece()
    {
    	return $this->belongsTo(Piece::class);
    }

    public function user()
    {
    	return $this->belongsTo(User::class);
    }

    public function folder()
    {
    	return $this->belongsTo(FavoriteFolder::class);
    }

    public function getFolderId(FavoriteFolder $folder = null)
    {
        return $folder ? $folder->id : null;
    }

    public function scopeToggle($query, User $user, Piece $piece, FavoriteFolder $folder = null)
    {
        $this->checkFolderOwnership($user, $folder);

        if ($record = $this->retrieve($user, $piece, $folder)->first()){
            $record->delete();

            if ($folder)
                $folder->sort();

            return false;
        }
        
        $this->addTo($user, $piece, $folder);
        
        if ($folder)
            $folder->sort();

        return true;
    }

    public function scopeRetrieve($query, User $user, Piece $piece, FavoriteFolder $folder = null)
    {
        return $query->where(['user_id' => $user->id, 'piece_id' => $piece->id, 'favorite_folder_id' => $this->getFolderId($folder)]);
    }

    public function scopeRemoveFrom($query, User $user, Piece $piece, FavoriteFolder $folder = null)
    {
        $this->checkFolderOwnership($user, $folder);

        return $this->retrieve($user, $piece, $folder)->firstOrFail()->delete();
    }

    public function scopeAddTo($query, User $user, Piece $piece, FavoriteFolder $folder = null)
    {
        $this->checkFolderOwnership($user, $folder);
        $this->checkForDuplicates($user, $piece, $folder);

        return $user->favorites()->attach($piece, ['favorite_folder_id' => $this->getFolderId($folder)]);
    }

    public function scopeMoveTo($query, User $user, Piece $piece, FavoriteFolder $folderFrom = null, FavoriteFolder $folderTo = null)
    {
        $this->removeFrom($user, $piece, $folderFrom);

        $this->addTo($user, $piece, $folderTo);
    }

    public function checkFolderOwnership($user, $folder = null)
    {
        if ($folder && ! $user->favoriteFolders()->find($folder->id))
            throw ValidationException::withMessages(['user_id' => 'You must own the folder to make changes to it.']);        
    }

    public function checkForDuplicates($user, $piece, $folder = null)
    {
        if ($this->retrieve($user, $piece, $folder)->count())
            throw ValidationException::withMessages(['user_id' => 'You already have this piece in this folder.']);
    }
}
