<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InfographForm extends FormRequest
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
            'name' => 'required|min:4|max:120',
            'description' => 'required|min:4|max:238',
            'cover_image' => 'sometimes|required|mimes:jpeg,jpg',
            'width' => 'required',
            'height' => 'required'
        ];
    }
}
