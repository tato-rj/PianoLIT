<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuizForm extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    private function usesEditor(): bool
    {
        return in_array($this->input('quiz_editor'), ['1', 1], true);
    }

    public function questions()
    {
        $quiz = [];
        foreach ($this->input('questions') as $index => $question) {
            $answers = [];
            ksort($question, SORT_NUMERIC);
            foreach ($question as $key => $value) {
                if ($key == 0) continue;
                if ($this->usesEditor()) {
                    $value = str_replace('[x]', '', (string) $value);
                    if (trim($value) === '') continue;
                    if ((string) $this->input('correct_answers.'.$index) === (string) $key) $value .= '[x]';
                }
                $answers[] = $value;
            }
            $text = $question[0];
            if (is_array($this->input('question_media'))) {
                $text = app(\App\Services\Admin\QuizQuestionMedia::class)->join($text, (string) $this->input('question_media.'.$index, ''));
            }
            $quiz[] = ['Q' => $text, 'A' => $answers];
        }
        return $quiz;
    }

    public function rules()
    {
        return [
            'title' => 'required|string|min:4|max:120',
            'description' => 'required|string|max:238',
            'questions' => 'required|array|min:1',
            'questions.*' => 'required|array',
            'questions.*.0' => 'required|string',
            'questions.*.*' => 'nullable|string',
            'level_id' => 'required|integer|exists:quiz_levels,id',
            'topics' => 'nullable|array',
            'topics.*' => 'required|integer|distinct|exists:quiz_topics,id',
            'quiz_editor' => 'sometimes|in:1',
            'correct_answers' => 'nullable|array',
            'correct_answers.*' => 'nullable|integer|min:1',
            'question_media' => 'nullable|array',
            'question_media.*' => 'nullable|string|max:2048',
            'cover_image' => 'sometimes|required|image|mimes:jpeg,jpg|max:1024',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!is_array($this->input('questions'))) return;
            foreach ($this->input('questions') as $index => $row) {
                if (!ctype_digit((string) $index)) {
                    $validator->errors()->add('questions', 'The question list is invalid. Reload the page and try again.');
                    continue;
                }
                if (!is_array($row)) continue;
                if (is_array($this->input('question_media'))) {
                    $value = $this->input('question_media.'.$index) ?? '';
                    $quiz = $this->route('quiz');
                    if (is_string($value) && !app(\App\Services\Admin\QuizQuestionMedia::class)->allowed($value, $quiz instanceof \App\Quiz\Quiz ? $quiz : null, $index)) {
                        $validator->errors()->add('question_media.'.$index, 'This file is unavailable. Choose another file or remove the media.');
                    }
                }
                $answers = [];
                foreach ($row as $key => $value) {
                    if (!ctype_digit((string) $key)) {
                        $validator->errors()->add('questions.'.$index, 'The answer list is invalid. Reload the page and try again.');
                        continue;
                    }
                    if ($key != 0 && is_string($value) && trim($this->usesEditor() ? str_replace('[x]', '', $value) : $value) !== '') $answers[$key] = $value;
                }
                if (count($answers) < 2) $validator->errors()->add('questions.'.$index, 'Enter at least two answers.');
                if ($this->usesEditor()) {
                    $correct = $this->input('correct_answers.'.$index);
                    if (!is_scalar($correct) || !isset($answers[$correct])) {
                        $validator->errors()->add('correct_answers.'.$index, 'Select a correct answer with text.');
                    }
                } elseif (count(array_filter($answers, fn ($answer) => strpos($answer, '[x]') !== false)) !== 1) {
                    $validator->errors()->add('questions.'.$index, 'Mark one correct answer with [x].');
                }
            }
        });
    }
}
