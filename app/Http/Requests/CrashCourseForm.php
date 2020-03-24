<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CrashCourseForm extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return ! $this->subscription_name && carbon($this->started_at)->lte(now()->subSeconds(3));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'first_name' => 'required|max:255',
            'email' => 'required|email|max:255'
        ];
    }
}
