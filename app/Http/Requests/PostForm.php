<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostForm extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => 'required|min:4|max:120',
            'description' => 'required|max:238',
            'content' => 'required',
            'cover_image' => 'sometimes|required|mimes:jpeg,jpg'
        ];
    }
}
