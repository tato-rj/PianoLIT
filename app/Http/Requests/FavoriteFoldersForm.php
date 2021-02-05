<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\{User, FavoriteFolder};

class FavoriteFoldersForm extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $this->user = $this->getUser();

        return $this->user && $this->checkFolderOwnership();
    }

    public function checkFolderOwnership()
    {
        if (! $this->folder && ! $this->folder_id)
            return true;

        $folder = $this->folder ?? FavoriteFolder::firstOrFail($this->folder_id);

        return ! $this->user->favoriteFolders()->find($folder)->isEmpty();
    }

    public function getUser()
    {
        return auth()->guard('web')->check() ? auth()->user() : User::firstOrFail($this->user_id);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'user_id' => 'sometimes|exists:users,id',
            'folder_id' => 'sometimes|exists:favorite_folders,id',
            'piece_id' => 'sometimes|exists:pieces,id',
            'name' => [
                'required', 
                'string',
                'min:3',
                'max:44',
                Rule::unique('favorite_folders')->where(function ($query) {
                    return $query->where(['user_id' => $this->user->id, 'name' => $this->name]);
                }),
            ]
        ];
    }

    public function messages()
    {
        return [
            'name.unique' => 'Looks like you already have a folder with this name'
        ];
    }
}
