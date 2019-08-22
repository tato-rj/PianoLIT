<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuizForm extends FormRequest
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

    public function questions()
    {
        $quiz = [];

        foreach ($this->questions as $index => $question) {
            $array = ['Q' => null, 'A' => []];
            foreach ($question as $key => $value) {
                if ($key == 0) {
                    $array['Q'] = $value;
                } else {
                    array_push($array['A'], $value);
                }
            }
            array_push($quiz, $array);
        }

        return $quiz;
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
            'questions' => 'required',
            'level_id' => 'required',
            'cover_image' => 'sometimes|required|mimes:jpeg,jpg'
        ];
    }
}
