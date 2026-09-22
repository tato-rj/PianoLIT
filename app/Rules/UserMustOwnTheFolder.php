<?php

namespace App\Rules;

use App\FavoriteFolder;
use Illuminate\Contracts\Validation\Rule;

class UserMustOwnTheFolder implements Rule
{
    protected $folderId;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($folderId)
    {
        $this->folderId = $folderId;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return FavoriteFolder::where('user_id', $value)->whereKey($this->folderId)->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'You must own this folder to make changes to it.';
    }
}
