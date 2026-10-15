<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StripeMembershipForm extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'operation_id' => 'required|uuid',
            'setup_intent' => ['required', 'string', 'max:255', 'regex:/^seti_[A-Za-z0-9]+$/D'],
        ];
    }
}
