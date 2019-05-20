<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ComposerForm extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $this->date_of_birth = carbon($this->date_of_birth)->format('Y-m-d');
        $this->date_of_death = $this->date_of_death ? carbon($this->date_of_death)->format('Y-m-d') : null;
        $this->period = strtolower($this->period);

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
            'name' => 'required|unique:composers|max:140',
            'biography' => 'required',
            'country_id' => 'required',
            'period' => 'required',
            'date_of_birth' => 'required'
        ];
    }
}
